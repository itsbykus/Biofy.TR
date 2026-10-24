<?php

namespace App\Services;

use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\User;
use App\Models\UserFeatureOverride;
use App\Models\UserPackageAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The single place where feature access and quotas are decided.
 *
 * Resolution order (first rule that applies wins, platform cap always applies last):
 *   1. Platform-wide hard constraints (feature disabled platform-wide / platform cap)
 *   2. Account status (suspended or pending deletion => nothing)
 *   3. User-specific override (not expired)
 *   4. Assigned package (current assignment, else the default package)
 *   5. Feature default value
 *
 * Integer features use -1 for "unlimited".
 */
class Entitlements
{
    public const UNLIMITED = -1;

    // Boolean features
    public const PREMIUM_THEMES = 'profile.custom_theme';

    public const ADVANCED_CUSTOMIZATION = 'profile.advanced_customization';

    public const ADVANCED_BLOCKS = 'profile.advanced_blocks';

    public const SCHEDULED_LINKS = 'profile.scheduled_links';

    public const REMOVE_BRANDING = 'profile.remove_branding';

    public const CUSTOM_DOMAIN = 'profile.custom_domain';

    public const SLUG_REDIRECTS = 'profile.slug_redirects';

    public const ANALYTICS_DETAILED = 'analytics.detailed';

    public const ANALYTICS_EXPORT = 'analytics.export';

    public const SEO_ADVANCED = 'seo.advanced';

    public const API_ACCESS = 'api.access';

    // Integer limits
    public const MAX_PAGES = 'profile.max_pages';

    public const MAX_LINKS = 'profile.max_links';

    public const MAX_SOCIAL_LINKS = 'profile.max_social_links';

    public const MAX_BLOCKS = 'profile.max_blocks';

    public const STORAGE_QUOTA_MB = 'storage.quota_mb';

    public const MAX_DOMAINS = 'domain.max_domains';

    public const API_RATE_LIMIT = 'api.rate_limit';

    public const API_MAX_TOKENS = 'api.max_tokens';

    public const ANALYTICS_HISTORY_DAYS = 'analytics.history_days';

    /** Google / Meta / TikTok pixels on the user's bio pages. */
    public const TRACKING_PIXELS = 'profile.tracking_pixels';

    /** How many short links (/s/code) a user may have. */
    public const MAX_SHORT_LINKS = 'shortlinks.max';

    /** @var array<string, array> per-request memo */
    private array $memo = [];

    /** @var array<int, Collection<string, FeatureDefinition>> */
    private array $definitionCache = [];

    public function __construct(private readonly Settings $settings)
    {
    }

    public function allows(User $user, string $key): bool
    {
        $value = $this->resolve($user)[$key]['value'] ?? false;

        return $value === true || (is_int($value) && $value !== 0);
    }

    public function limit(User $user, string $key): int
    {
        $value = $this->resolve($user)[$key]['value'] ?? 0;

        return is_bool($value) ? (int) $value : (int) $value;
    }

    /** True when a user who currently has $used items may add $adding more. */
    public function withinLimit(User $user, string $key, int $used, int $adding = 1): bool
    {
        $limit = $this->limit($user, $key);

        return $limit === self::UNLIMITED || ($used + $adding) <= $limit;
    }

    /** @return array<string, array{value: bool|int, source: string, definition: FeatureDefinition}> */
    public function resolve(User $user): array
    {
        $cacheKey = sprintf('entitlements:%d:%d:%d', $user->id, $user->entitlements_version, $this->settings->int('system.features_version'));
        // The account status is applied on top of the cached values, so it is part of the memo key.
        $memoKey = $cacheKey.':'.($user->isActive() ? 'active' : 'inactive');

        if (isset($this->memo[$memoKey])) {
            return $this->memo[$memoKey];
        }

        // Only plain values are cached; definitions are re-attached from the (memoised) catalogue.
        $plain = Cache::remember($cacheKey, now()->addHour(), fn () => $this->compute($user));

        // Account status is never served from cache: suspension must take effect immediately.
        $active = $user->isActive();
        $definitions = $this->definitions();
        $resolved = [];

        foreach ($definitions as $key => $definition) {
            $entry = $plain[$key] ?? ['value' => $this->cast($definition, $definition->default_value), 'source' => 'default'];

            if (! $active && $entry['source'] !== 'platform') {
                $entry = ['value' => $definition->isBoolean() ? false : 0, 'source' => 'account'];
            }

            // Expiring overrides are re-checked against the clock even when cached.
            if ($entry['source'] === 'override' && isset($entry['expires_at']) && $entry['expires_at'] <= now()->getTimestamp()) {
                Cache::forget($cacheKey);
                unset($this->memo[$memoKey]);

                return $this->resolve($user->fresh());
            }

            $resolved[$key] = $entry + ['definition' => $definition];
        }

        return $this->memo[$memoKey] = $resolved;
    }

    public function currentAssignment(User $user): ?UserPackageAssignment
    {
        return $user->packageAssignments()->current()->with('package')->latest('starts_at')->latest('id')->first();
    }

    public function currentPackage(User $user): ?Package
    {
        return $this->currentAssignment($user)?->package ?? $this->defaultPackage();
    }

    public function defaultPackage(): ?Package
    {
        $key = (string) $this->settings->get('registration.default_package');

        return Package::query()->available()->where('key', $key)->first()
            ?? Package::query()->available()->where('is_default', true)->orderBy('sort_order')->first();
    }

    /** Invalidate every user's cached entitlements (package/feature catalogue changed). */
    public function flushAll(): void
    {
        $this->settings->set('system.features_version', $this->settings->int('system.features_version') + 1);
        $this->memo = [];
    }

    public function flushUser(User $user): void
    {
        $user->bumpEntitlementsVersion();
        $this->memo = [];
    }

    /** @return Collection<string, FeatureDefinition> */
    public function definitions(): Collection
    {
        $version = $this->settings->int('system.features_version');

        return $this->definitionCache[$version] ??= FeatureDefinition::query()->orderBy('group')->orderBy('sort_order')->get()->keyBy('key');
    }

    private function compute(User $user): array
    {
        $definitions = $this->definitions();
        $package = $this->currentPackage($user);
        $package?->loadMissing('features');

        $overrides = UserFeatureOverride::query()->where('user_id', $user->id)->get()
            ->reject(fn (UserFeatureOverride $o) => $o->isExpired())
            ->keyBy('feature_key');

        $result = [];

        foreach ($definitions as $key => $definition) {
            // 1. Platform hard constraint.
            if (! $definition->platform_enabled) {
                $result[$key] = ['value' => $definition->isBoolean() ? false : 0, 'source' => 'platform'];

                continue;
            }

            // 3-5. Override > package > default. (2, account status, is applied at read time.)
            if ($override = $overrides->get($key)) {
                $entry = ['value' => $this->cast($definition, $override->value), 'source' => 'override'];

                if ($override->expires_at) {
                    $entry['expires_at'] = $override->expires_at->getTimestamp();
                }
            } elseif ($package && ($packageValue = $package->featureValue($key)) !== null) {
                $entry = ['value' => $this->cast($definition, $packageValue), 'source' => 'package'];
            } else {
                $entry = ['value' => $this->cast($definition, $definition->default_value), 'source' => 'default'];
            }

            $entry['value'] = $this->applyCap($definition, $entry['value']);
            $result[$key] = $entry;
        }

        return $result;
    }

    private function applyCap(FeatureDefinition $definition, bool|int $value): bool|int
    {
        if ($definition->isBoolean() || $definition->platform_cap === null) {
            return $value;
        }

        $cap = $definition->platform_cap;

        if ($cap === self::UNLIMITED) {
            return $value;
        }

        return ($value === self::UNLIMITED || $value > $cap) ? $cap : $value;
    }

    private function cast(FeatureDefinition $definition, ?string $value): bool|int
    {
        if ($definition->isBoolean()) {
            return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
        }

        return max(self::UNLIMITED, (int) $value);
    }
}
