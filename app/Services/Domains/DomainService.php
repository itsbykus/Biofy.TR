<?php

namespace App\Services\Domains;

use App\Exceptions\UserFacingException;
use App\Models\CustomDomain;
use App\Models\DomainVerification;
use App\Models\Profile;
use App\Models\User;
use App\Services\Entitlements;
use App\Services\Settings;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainService
{
    private const CACHE_KEY = 'biofy.domains.map';

    /** Consecutive failed re-checks before a verified domain is taken offline. */
    private const MAX_RECHECK_FAILURES = 3;

    public function __construct(
        private readonly DnsResolver $dns,
        private readonly HttpsProbe $https,
        private readonly Entitlements $entitlements,
        private readonly Settings $settings,
    ) {
    }

    public function normalizeHostname(string $input): ?string
    {
        $host = Str::lower(trim($input));
        $host = preg_replace('#^[a-z]+://#', '', $host);
        $host = explode('/', $host)[0];
        $host = explode('?', $host)[0];
        $host = preg_replace('/:\d+$/', '', $host);
        $host = rtrim($host, '.');

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) || ! SafeUrl::isValidHost($host) || substr_count($host, '.') < 1) {
            return null;
        }

        return $host;
    }

    public function isPlatformHost(string $host): bool
    {
        foreach ($this->platformHosts() as $platform) {
            if ($host === $platform || str_ends_with($host, '.'.$platform)) {
                return true;
            }
        }

        return in_array($host, ['localhost'], true) || str_ends_with($host, '.localhost') || str_ends_with($host, '.local');
    }

    /** @return list<string> */
    public function platformHosts(): array
    {
        $host = Str::lower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $base = preg_replace('/^www\./', '', $host);

        return array_values(array_unique(array_filter([$host, $base, 'www.'.$base])));
    }

    public function add(User $user, string $hostnameInput, ?Profile $profile = null): CustomDomain
    {
        if (! $this->settings->bool('domains.enabled')) {
            throw new UserFacingException(__('domains.disabled_platform'));
        }

        if (! $this->entitlements->allows($user, Entitlements::CUSTOM_DOMAIN)) {
            throw new UserFacingException(__('domains.requires_upgrade'));
        }

        $count = CustomDomain::query()->where('user_id', $user->id)->count();

        if (! $this->entitlements->withinLimit($user, Entitlements::MAX_DOMAINS, $count)) {
            throw new UserFacingException(__('domains.limit_reached'));
        }

        $hostname = $this->normalizeHostname($hostnameInput);

        if ($hostname === null) {
            throw ValidationException::withMessages(['hostname' => __('domains.invalid')]);
        }

        if ($this->isPlatformHost($hostname)) {
            throw ValidationException::withMessages(['hostname' => __('domains.platform_host')]);
        }

        if (CustomDomain::query()->where('hostname', $hostname)->exists()) {
            throw ValidationException::withMessages(['hostname' => __('domains.taken')]);
        }

        if ($profile && $profile->user_id !== $user->id) {
            throw ValidationException::withMessages(['profile_id' => __('domains.profile_not_owned')]);
        }

        $domain = CustomDomain::query()->create([
            'user_id' => $user->id,
            'profile_id' => $profile?->id,
            'hostname' => $hostname,
            'verification_token' => bin2hex(random_bytes(16)),
            'status' => CustomDomain::STATUS_PENDING,
        ]);

        $this->flushCache();

        return $domain;
    }

    public function assignProfile(CustomDomain $domain, ?Profile $profile): void
    {
        if ($profile && $profile->user_id !== $domain->user_id) {
            throw ValidationException::withMessages(['profile_id' => __('domains.profile_not_owned')]);
        }

        $domain->update(['profile_id' => $profile?->id]);
        $this->flushCache();
    }

    public function remove(CustomDomain $domain): void
    {
        $domain->delete();
        $this->flushCache();
    }

    /**
     * Runs the DNS ownership check (TXT), the optional routing check (CNAME/A) and,
     * once DNS passes, the HTTPS check. Safe to call repeatedly (DNS propagation).
     */
    public function verify(CustomDomain $domain): CustomDomain
    {
        if ($domain->status === CustomDomain::STATUS_DISABLED) {
            return $domain;
        }

        $txtValues = array_map('trim', $this->dns->txt($domain->txtRecordName()));
        $txtPassed = in_array($domain->txtRecordValue(), $txtValues, true);
        $this->logCheck($domain, 'dns_txt', $txtPassed, $txtPassed ? 'ok' : 'record_not_found');

        [$targetPassed, $targetDetails] = $this->checkRouting($domain->hostname);
        $this->logCheck($domain, 'dns_target', $targetPassed, $targetDetails);

        $domain->last_checked_at = now();
        $domain->check_count++;

        if ($txtPassed && $targetPassed) {
            $domain->status = CustomDomain::STATUS_VERIFIED;
            $domain->dns_verified_at ??= now();
            $domain->last_error = null;
            $domain->save();
            $this->flushCache();

            [$httpsPassed, $httpsDetails] = $this->https->check($domain->hostname, $domain->verification_token);
            $this->logCheck($domain, 'https', $httpsPassed, $httpsDetails);
            $domain->ssl_status = $httpsPassed ? CustomDomain::SSL_ACTIVE : CustomDomain::SSL_FAILED;
            $domain->ssl_checked_at = now();
            $domain->last_error = $httpsPassed ? null : 'https_'.$httpsDetails;
        } else {
            $domain->last_error = ! $txtPassed ? 'txt_missing' : 'target_mismatch';

            // A previously verified domain is only taken offline after repeated failures (DNS hiccups).
            if ($domain->status === CustomDomain::STATUS_VERIFIED) {
                $recentFailures = $domain->verifications()->where('check', 'dns_txt')->limit(self::MAX_RECHECK_FAILURES)->get()
                    ->filter(fn ($v) => ! $v->passed)->count();

                if ($recentFailures >= self::MAX_RECHECK_FAILURES) {
                    $domain->status = CustomDomain::STATUS_PENDING;
                    $domain->ssl_status = CustomDomain::SSL_UNKNOWN;
                }
            }
        }

        $domain->save();
        $this->flushCache();

        DomainVerification::query()->where('custom_domain_id', $domain->id)
            ->whereNotIn('id', DomainVerification::query()->where('custom_domain_id', $domain->id)->latest('id')->limit(30)->pluck('id'))
            ->delete();

        return $domain->fresh();
    }

    public function disable(CustomDomain $domain, string $reason): void
    {
        $domain->forceFill(['status' => CustomDomain::STATUS_DISABLED, 'disabled_reason' => $reason])->save();
        $this->flushCache();
    }

    public function enable(CustomDomain $domain): void
    {
        $domain->forceFill(['status' => CustomDomain::STATUS_PENDING, 'disabled_reason' => null])->save();
        $this->flushCache();
    }

    /**
     * Host lookup used on every request from a non-platform host.
     *
     * @return array{id: int, profile_id: ?int, status: string, secure: bool, token: string}|null
     */
    public function lookup(string $host): ?array
    {
        $host = Str::lower(rtrim($host, '.'));
        $map = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return CustomDomain::query()
                ->where('status', '!=', CustomDomain::STATUS_DISABLED)
                ->get(['id', 'hostname', 'profile_id', 'status', 'ssl_status', 'verification_token'])
                ->mapWithKeys(fn (CustomDomain $d) => [$d->hostname => [
                    'id' => $d->id,
                    'profile_id' => $d->profile_id,
                    'status' => $d->status,
                    'secure' => $d->ssl_status === CustomDomain::SSL_ACTIVE,
                    'token' => $d->verification_token,
                ]])->all();
        });

        return $map[$host] ?? null;
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{0: bool, 1: string} */
    private function checkRouting(string $hostname): array
    {
        $cnameTarget = Str::lower(rtrim((string) $this->settings->get('domains.cname_target'), '.'));
        $ip = trim((string) $this->settings->get('domains.a_record_ip'));

        if ($cnameTarget === '' && $ip === '') {
            return [true, 'not_configured'];
        }

        if ($cnameTarget !== '' && in_array($cnameTarget, $this->dns->cname($hostname), true)) {
            return [true, 'cname_ok'];
        }

        if ($ip !== '' && in_array($ip, $this->dns->a($hostname), true)) {
            return [true, 'a_ok'];
        }

        return [false, 'target_mismatch'];
    }

    private function logCheck(CustomDomain $domain, string $check, bool $passed, string $details): void
    {
        DomainVerification::query()->create([
            'custom_domain_id' => $domain->id,
            'check' => $check,
            'passed' => $passed,
            'details' => Str::limit($details, 490),
            'created_at' => now(),
        ]);
    }
}
