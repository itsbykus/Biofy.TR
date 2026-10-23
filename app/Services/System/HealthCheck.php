<?php

namespace App\Services\System;

use App\Services\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class HealthCheck
{
    public function __construct(private readonly Settings $settings)
    {
    }

    /** Cheap checks (safe on every /health request). @return array<string, array{ok: bool, detail?: string}> */
    public function basic(): array
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = ['ok' => true];
        } catch (Throwable) {
            $checks['database'] = ['ok' => false, 'detail' => 'unreachable'];
        }

        try {
            $key = 'health:'.Str::random(8);
            Cache::put($key, 1, 10);
            $checks['cache'] = ['ok' => Cache::pull($key) === 1];
        } catch (Throwable) {
            $checks['cache'] = ['ok' => false];
        }

        foreach (['storage' => storage_path('framework'), 'logs' => storage_path('logs'), 'media' => public_path('media')] as $name => $path) {
            $checks['writable_'.$name] = ['ok' => is_dir($path) && is_writable($path)];
        }

        return $checks;
    }

    /** Full report for the admin panel. */
    public function full(): array
    {
        $checks = $this->basic();
        $checks['php_version'] = ['ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'detail' => PHP_VERSION];
        $checks['debug_off'] = ['ok' => ! config('app.debug'), 'detail' => config('app.debug') ? 'APP_DEBUG=true' : 'off'];
        $checks['https'] = ['ok' => str_starts_with((string) config('app.url'), 'https://'), 'detail' => (string) config('app.url')];
        $checks['mail_configured'] = ['ok' => $this->settings->get('mail.mailer') === 'smtp' && $this->settings->get('mail.host') !== '', 'detail' => (string) $this->settings->get('mail.mailer')];

        $lastCron = $this->settings->get('cron.last_run_at');
        $checks['cron'] = ['ok' => $lastCron !== null && \Illuminate\Support\Carbon::parse($lastCron)->diffInHours(now()) < 24, 'detail' => $lastCron ?? 'never'];

        $free = @disk_free_space(storage_path());
        $checks['disk_space'] = ['ok' => $free === false || $free > 200 * 1024 * 1024, 'detail' => $free === false ? 'unknown' : round($free / 1024 / 1024).' MB'];

        foreach (['gd' => 'gd', 'zip' => 'zip', 'sodium' => 'sodium', 'curl' => 'curl', 'fileinfo' => 'fileinfo'] as $label => $ext) {
            $checks['ext_'.$label] = ['ok' => extension_loaded($ext)];
        }

        $checks['webp'] = ['ok' => function_exists('imagewebp')];

        return $checks;
    }

    /**
     * Requests sensitive paths over HTTP and reports any that are publicly readable.
     * Network-bound; run on demand only.
     *
     * @return array<string, array{status: int|string, exposed: bool}>
     */
    public function exposureProbe(): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $paths = ['/.env', '/composer.json', '/storage/logs/laravel.log', '/vendor/autoload.php', '/install/installer.php',
            '/storage/app/private/installed.lock', '/config/app.php', '/database/migrations/', '/artisan', '/backups/'];
        $results = [];

        foreach ($paths as $path) {
            try {
                $response = Http::timeout(5)->withoutRedirecting()->withOptions(['verify' => false])->get($base.$path);
                $status = $response->status();
                $body = $response->body();
                // 200 from Laravel's 404 page would not contain these markers; a real leak would.
                $exposed = $status === 200 && (str_contains($body, 'APP_KEY') || str_contains($body, '<?php') || str_contains($body, '"require"') || str_contains($body, 'installed_at') || str_contains($body, '[stacktrace]') || str_contains($body, 'Index of'));
                $results[$path] = ['status' => $status, 'exposed' => $exposed];
            } catch (Throwable) {
                $results[$path] = ['status' => 'error', 'exposed' => false];
            }
        }

        return $results;
    }
}
