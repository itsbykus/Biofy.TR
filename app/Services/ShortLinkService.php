<?php

namespace App\Services;

use App\Exceptions\UserFacingException;
use App\Models\ShortLink;
use App\Models\User;
use App\Support\SafeUrl;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Short links served at /s/{code}: creation rules, quota and click counting. */
class ShortLinkService
{
    public const CODE_PATTERN = '/^[a-z0-9](?:[a-z0-9_-]{1,38}[a-z0-9])?$/';

    /** Generated codes avoid look-alike characters (0/o, 1/l). */
    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyz23456789';

    public function __construct(private readonly Entitlements $entitlements)
    {
    }

    public function create(User $owner, string $target, ?string $alias = null, ?string $title = null): ShortLink
    {
        $used = ShortLink::query()->where('user_id', $owner->id)->count();

        if (! $this->entitlements->withinLimit($owner, Entitlements::MAX_SHORT_LINKS, $used)) {
            throw new UserFacingException(__('shortlinks.limit_reached'));
        }

        $link = new ShortLink([
            'target_url' => $this->target($target),
            'title' => $this->title($title),
            'code' => $alias !== null && trim($alias) !== '' ? $this->alias($alias) : $this->generateCode(),
        ]);
        $link->user_id = $owner->id;
        $link->save();

        return $link;
    }

    public function update(ShortLink $link, string $target, ?string $title): ShortLink
    {
        $link->fill(['target_url' => $this->target($target), 'title' => $this->title($title)])->save();

        return $link;
    }

    /** Counts a visit (bots and link-preview fetchers excluded) and returns the destination. */
    public function hit(ShortLink $link, Request $request): string
    {
        if (! UserAgent::isBot($request->userAgent())) {
            // Atomic increment: no read-modify-write race between parallel clicks.
            ShortLink::query()->whereKey($link->id)->update(['clicks' => DB::raw('clicks + 1'), 'last_clicked_at' => now()]);
        }

        return $link->target_url;
    }

    private function target(string $target): string
    {
        $url = SafeUrl::normalize($target);

        if ($url === null) {
            throw ValidationException::withMessages(['target_url' => __('validation.custom.url.unsafe')]);
        }

        // A short link pointing at another short link of this site would allow redirect loops.
        $own = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($host === $own && str_starts_with($path, '/s/')) {
            throw ValidationException::withMessages(['target_url' => __('shortlinks.no_chains')]);
        }

        return $url;
    }

    private function alias(string $alias): string
    {
        $alias = Str::lower(trim($alias));

        if (! preg_match(self::CODE_PATTERN, $alias)) {
            throw ValidationException::withMessages(['code' => __('shortlinks.alias_format')]);
        }

        if (ShortLink::query()->where('code', $alias)->exists()) {
            throw ValidationException::withMessages(['code' => __('shortlinks.alias_taken')]);
        }

        return $alias;
    }

    private function title(?string $title): ?string
    {
        $title = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $title));

        return $title === '' ? null : mb_substr($title, 0, 120);
    }

    private function generateCode(): string
    {
        for ($length = 6; $length <= 10; $length++) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = '';

                for ($i = 0; $i < $length; $i++) {
                    $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
                }

                if (! ShortLink::query()->where('code', $code)->exists()) {
                    return $code;
                }
            }
        }

        throw new UserFacingException(__('shortlinks.code_failed'));
    }
}
