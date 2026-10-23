<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Support\SettingDefinitions;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

/**
 * Runtime configuration stored in `system_settings`, cached as a single array.
 * Secrets are encrypted at rest and never returned by all()/public helpers.
 */
class Settings
{
    private const CACHE_KEY = 'biofy.settings.v1';

    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = SettingDefinitions::get($key);
        $values = $this->load();

        if (array_key_exists($key, $values)) {
            return $this->decode($key, $values[$key], $definition);
        }

        return $default ?? ($definition['default'] ?? null);
    }

    /** Translatable setting resolved for the current (or given) locale with fallback. */
    public function text(string $key, ?string $locale = null): string
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return (string) $value;
        }

        $locale ??= app()->getLocale();

        foreach (array_unique([$locale, 'tr', 'en']) as $candidate) {
            if (! empty($value[$candidate])) {
                return (string) $value[$candidate];
            }
        }

        return '';
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key);
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function hasSecret(string $key): bool
    {
        $values = $this->load();

        return isset($values[$key]) && $values[$key] !== null && $values[$key] !== '';
    }

    public function set(string $key, mixed $value, ?int $userId = null): void
    {
        $definition = SettingDefinitions::get($key);

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown setting [{$key}].");
        }

        $encrypted = $definition['type'] === 'secret';
        $stored = $this->encode($value, $definition);

        if ($encrypted && $stored !== null) {
            $stored = Crypt::encryptString($stored);
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypted, 'updated_by' => $userId],
        );

        $this->flush();
    }

    public function setMany(array $values, ?int $userId = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $userId);
        }
    }

    public function forget(string $key): void
    {
        SystemSetting::query()->where('key', $key)->delete();
        $this->flush();
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function load(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            $cached = Cache::get(self::CACHE_KEY);

            if (is_array($cached)) {
                return $this->loaded = $cached;
            }

            // Before installation the table doesn't exist: use defaults but neither cache nor
            // memoise that state (the installer creates the table later in the same process).
            if (! Schema::hasTable('system_settings')) {
                return [];
            }

            $values = SystemSetting::query()->pluck('value', 'key')->all();
            Cache::forever(self::CACHE_KEY, $values);

            return $this->loaded = $values;
        } catch (Throwable $e) {
            // The app must keep serving (with defaults) if the settings store is briefly unavailable.
            Log::warning('Settings could not be loaded: '.$e->getMessage());

            return $this->loaded = [];
        }
    }

    private function encode(mixed $value, array $definition): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($definition['type']) {
            'bool' => $value ? '1' : '0',
            'int' => (string) (int) $value,
            'json', 'translatable' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'media' => $value === '' ? null : (string) (int) $value,
            default => (string) $value,
        };
    }

    private function decode(string $key, ?string $raw, ?array $definition): mixed
    {
        $type = $definition['type'] ?? 'string';

        if ($raw === null) {
            return $definition['default'] ?? null;
        }

        if ($type === 'secret') {
            try {
                return Crypt::decryptString($raw);
            } catch (DecryptException) {
                // Happens when APP_KEY changed (e.g. restore on another install). Treat as unset.
                Log::warning("Encrypted setting [{$key}] could not be decrypted; it must be re-entered.");

                return null;
            }
        }

        return match ($type) {
            'bool' => $raw === '1',
            'int', 'media' => (int) $raw,
            'json', 'translatable' => json_decode($raw, true) ?? ($definition['default'] ?? null),
            default => $raw,
        };
    }
}
