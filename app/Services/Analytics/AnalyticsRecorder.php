<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Profile;
use App\Services\Settings;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records profile views and link clicks without cookies and without storing IP
 * addresses. A visitor is a keyed hash of (IP, user agent, profile) with a salt
 * that rotates daily, so the same person can't be followed across days or pages.
 */
class AnalyticsRecorder
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function recordView(Profile $profile, Request $request): bool
    {
        return $this->record($profile, $request, AnalyticsEvent::TYPE_VIEW, null, null);
    }

    public function recordClick(Profile $profile, int $blockId, ?int $item, Request $request): bool
    {
        return $this->record($profile, $request, AnalyticsEvent::TYPE_CLICK, $blockId, $item);
    }

    private function record(Profile $profile, Request $request, string $type, ?int $blockId, ?int $item): bool
    {
        if ($this->shouldIgnore($request)) {
            return false;
        }

        try {
            $now = now();
            $localDate = $now->copy()->setTimezone($this->timezone())->toDateString();
            $hash = $this->visitorHash($profile, $request, $localDate);

            $window = $type === AnalyticsEvent::TYPE_VIEW
                ? $now->copy()->subMinutes((int) config('biofy.analytics.view_dedupe_minutes'))
                : $now->copy()->subSeconds((int) config('biofy.analytics.click_dedupe_seconds'));

            // Refresh storms / double clicks from the same visitor are not counted again.
            $duplicate = AnalyticsEvent::query()
                ->where('profile_id', $profile->id)
                ->where('type', $type)
                ->where('visitor_hash', $hash)
                ->where('created_at', '>=', $window)
                ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
                ->exists();

            if ($duplicate) {
                return false;
            }

            $isUnique = false;

            if ($type === AnalyticsEvent::TYPE_VIEW) {
                $dayStart = $now->copy()->setTimezone($this->timezone())->startOfDay()->utc();
                $isUnique = ! AnalyticsEvent::query()
                    ->where('profile_id', $profile->id)
                    ->where('type', AnalyticsEvent::TYPE_VIEW)
                    ->where('visitor_hash', $hash)
                    ->where('created_at', '>=', $dayStart)
                    ->exists();
            }

            DB::transaction(function () use ($profile, $type, $blockId, $item, $hash, $isUnique, $request, $now, $localDate) {
                AnalyticsEvent::query()->create([
                    'profile_id' => $profile->id,
                    'type' => $type,
                    'block_id' => $blockId,
                    'item_index' => $item,
                    'visitor_hash' => $hash,
                    'is_unique' => $isUnique,
                    'referrer_host' => $this->referrerHost($request),
                    'device' => UserAgent::device($request->userAgent()),
                    'browser' => UserAgent::browser($request->userAgent()),
                    'country' => $this->country($request),
                    'created_at' => $now,
                ]);

                if ($type === AnalyticsEvent::TYPE_VIEW) {
                    $this->increment($profile->id, 0, $localDate, ['views' => 1, 'unique_visitors' => $isUnique ? 1 : 0]);
                } else {
                    $this->increment($profile->id, 0, $localDate, ['clicks' => 1]);
                    $this->increment($profile->id, (int) $blockId, $localDate, ['clicks' => 1]);
                }
            });

            return true;
        } catch (Throwable $e) {
            // Analytics must never break the public page.
            Log::warning('Analytics event not recorded: '.$e->getMessage());

            return false;
        }
    }

    public function shouldIgnore(Request $request): bool
    {
        if (UserAgent::isBot($request->userAgent())) {
            return true;
        }

        // Browser prefetch / prerender requests are not real visits.
        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose') ?? $request->header('X-Moz')));

        return str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender');
    }

    public function visitorHash(Profile $profile, Request $request, string $localDate): string
    {
        $salt = $this->saltFor($localDate);

        return substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.$profile->id, $salt), 0, 32);
    }

    public function saltFor(string $date): string
    {
        $salt = DB::table('analytics_salts')->where('date', $date)->value('salt');

        if ($salt) {
            return $salt;
        }

        DB::table('analytics_salts')->insertOrIgnore(['date' => $date, 'salt' => bin2hex(random_bytes(32))]);

        return (string) DB::table('analytics_salts')->where('date', $date)->value('salt');
    }

    private function increment(int $profileId, int $blockId, string $date, array $counts): void
    {
        $row = ['profile_id' => $profileId, 'block_id' => $blockId, 'date' => $date, 'views' => 0, 'unique_visitors' => 0, 'clicks' => 0];
        $update = [];

        foreach ($counts as $column => $amount) {
            $row[$column] = $amount;
            $update[$column] = DB::raw($column.' + '.(int) $amount);
        }

        DB::table('analytics_daily_summaries')->upsert([$row], ['profile_id', 'block_id', 'date'], $update);
    }

    private function referrerHost(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer');
        $host = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::lower(preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host));

        // Internal navigation is not a referrer.
        if ($host === Str::lower((string) parse_url((string) config('app.url'), PHP_URL_HOST)) || $host === $request->getHost()) {
            return null;
        }

        return Str::limit($host, 190, '');
    }

    private function country(Request $request): ?string
    {
        // Only used when the operator confirmed the site is behind Cloudflare; never guessed.
        if (! $this->settings->bool('analytics.trust_cf_country')) {
            return null;
        }

        $country = strtoupper((string) $request->header('CF-IPCountry'));

        return preg_match('/^[A-Z]{2}$/', $country) && ! in_array($country, ['XX', 'T1'], true) ? $country : null;
    }

    private function timezone(): string
    {
        return (string) ($this->settings->get('site.timezone') ?: 'UTC');
    }
}
