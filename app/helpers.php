<?php

use App\Services\Settings;
use Illuminate\Support\Carbon;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('setting_text')) {
    /** Translatable setting in the current locale (with fallback). */
    function setting_text(string $key): string
    {
        return app(Settings::class)->text($key);
    }
}

if (! function_exists('local_time')) {
    /** Format a UTC timestamp in the viewer's (or site's) time zone. */
    function local_time(mixed $value, string $format = 'd.m.Y H:i'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return Carbon::parse($value)->setTimezone(local_timezone())->translatedFormat($format);
    }
}

if (! function_exists('local_timezone')) {
    /** The viewer's time zone, else the site's. Dates typed into forms are read in it. */
    function local_timezone(): string
    {
        return auth()->user()?->timezone ?: (app(Settings::class)->get('site.timezone') ?: 'UTC');
    }
}

if (! function_exists('human_bytes')) {
    function human_bytes(int|float|null $bytes): string
    {
        $bytes = (float) $bytes;

        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return ($unit === 'B' ? (int) $bytes : number_format($bytes, 1, ',', '.')).' '.$unit;
            }

            $bytes /= 1024;
        }

        return '';
    }
}

if (! function_exists('limit_label')) {
    /** "Unlimited" for -1, otherwise the number. */
    function limit_label(int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? __('common.yes') : __('common.no');
        }

        return $value === -1 ? __('common.unlimited') : number_format($value, 0, ',', '.');
    }
}

if (! function_exists('num')) {
    function num(int|float|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, app()->getLocale() === 'tr' ? ',' : '.', app()->getLocale() === 'tr' ? '.' : ',');
    }
}

if (! function_exists('money')) {
    function money(mixed $amount, string $currency): string
    {
        $symbols = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
        $value = num((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2);

        return isset($symbols[$currency]) ? ($currency === 'TRY' ? $value.' '.$symbols[$currency] : $symbols[$currency].$value) : $value.' '.$currency;
    }
}

if (! function_exists('audit_label')) {
    /** Audit action ("user.suspended") -> translated label; unknown actions fall back to the raw key. */
    function audit_label(string $action): string
    {
        $key = 'audit.actions.'.str_replace('.', '_', $action);
        $label = __($key);

        return $label === $key ? $action : $label;
    }
}

if (! function_exists('setting_label')) {
    /** Label / help text of a settings field ("site.name") in the admin panel. */
    function setting_label(string $key, string $kind = 'fields'): ?string
    {
        $translationKey = 'admin.settings.'.$kind.'.'.str_replace('.', '_', $key);
        $label = __($translationKey);

        return $label === $translationKey ? ($kind === 'fields' ? $key : null) : $label;
    }
}

if (! function_exists('safe_internal_url')) {
    /** Menu/CTA links: internal paths or https URLs only. */
    function safe_internal_url(?string $url): string
    {
        $url = trim((string) $url);

        if (preg_match('#^/(?!/)#', $url)) {
            return url($url);
        }

        return \App\Support\SafeUrl::normalize($url) ?? '#';
    }
}
