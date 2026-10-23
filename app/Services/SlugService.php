<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfileSlugRedirect;
use App\Models\ReservedUsername;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Central rules for usernames and profile slugs (they share one namespace with
 * the platform's own routes). Comparison is always case-insensitive because
 * values are normalised to lower case before storage and lookup.
 */
class SlugService
{
    private const RESERVED_CACHE_KEY = 'biofy.reserved_words';

    public function normalize(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    public function hasValidFormat(string $slug): bool
    {
        return (bool) preg_match(config('biofy.slug.pattern'), $slug);
    }

    public function isReserved(string $slug): bool
    {
        $slug = $this->normalize($slug);

        return in_array($slug, config('biofy.reserved_slugs'), true)
            || in_array($slug, $this->adminReservedWords(), true);
    }

    /** @return list<string> */
    public function adminReservedWords(): array
    {
        return Cache::rememberForever(self::RESERVED_CACHE_KEY, fn () => ReservedUsername::query()->pluck('word')->all());
    }

    public function flushReserved(): void
    {
        Cache::forget(self::RESERVED_CACHE_KEY);
    }

    /**
     * Returns null when usable, otherwise a translation key describing why not.
     */
    public function problemWithProfileSlug(string $slug, ?Profile $ignore = null): ?string
    {
        $slug = $this->normalize($slug);

        if (! $this->hasValidFormat($slug)) {
            return 'validation.custom.slug.format';
        }

        if ($this->isReserved($slug)) {
            return 'validation.custom.slug.reserved';
        }

        $taken = Profile::query()->where('slug', $slug)
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->exists();

        if ($taken) {
            return 'validation.custom.slug.taken';
        }

        // A slug kept as a redirect for someone else's renamed profile is not free yet.
        $redirect = ProfileSlugRedirect::query()->where('old_slug', $slug)->first();

        if ($redirect && ($redirect->expires_at === null || $redirect->expires_at->isFuture())
            && (! $ignore || $redirect->profile_id !== $ignore->getKey())) {
            return 'validation.custom.slug.taken';
        }

        return null;
    }

    public function problemWithUsername(string $username, ?User $ignore = null): ?string
    {
        $username = $this->normalize($username);

        if (! $this->hasValidFormat($username)) {
            return 'validation.custom.username.format';
        }

        if ($this->isReserved($username)) {
            return 'validation.custom.username.reserved';
        }

        $taken = User::query()->where('username', $username)
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->exists();

        return $taken ? 'validation.custom.username.taken' : null;
    }

    /** Suggest a free slug derived from $base (used for Google sign-up and new pages). */
    public function suggest(string $base): string
    {
        $candidate = Str::of(Str::ascii($base))->lower()->replaceMatches('/[^a-z0-9_-]+/', '')->trim('-_')->limit(24, '')->value();

        if (strlen($candidate) < 3) {
            $candidate = 'user'.$candidate;
        }

        $slug = $candidate;
        $i = 1;

        while ($this->problemWithProfileSlug($slug) !== null || $this->problemWithUsername($slug) !== null) {
            $slug = $candidate.random_int(10, 9999);

            if ($i++ > 20) {
                $slug = 'user'.Str::lower(Str::random(8));
            }
        }

        return $slug;
    }
}
