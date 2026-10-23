<?php

namespace BiofyInstaller;

/**
 * Server requirement checks for the web installer. Plain PHP (no framework) so it
 * also works when vendor/ is incomplete. Each check: [key, ok, required, detail].
 */
final class Requirements
{
    public const MIN_PHP = '8.3.0';

    public const REQUIRED_EXTENSIONS = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'dom', 'ctype', 'json', 'fileinfo', 'gd', 'zip', 'session', 'filter'];

    public const RECOMMENDED_EXTENSIONS = ['curl', 'sodium', 'intl', 'bcmath'];

    public function __construct(
        private readonly string $base,
        private readonly string $public,
    ) {
    }

    /** @return list<array{key: string, ok: bool, required: bool, detail: string}> */
    public function all(): array
    {
        $checks = [];
        $checks[] = $this->check('php', version_compare(PHP_VERSION, self::MIN_PHP, '>='), true, PHP_VERSION);

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $checks[] = $this->check('ext:'.$ext, extension_loaded($ext), true, '');
        }

        foreach (self::RECOMMENDED_EXTENSIONS as $ext) {
            $checks[] = $this->check('ext:'.$ext, extension_loaded($ext), false, '');
        }

        $checks[] = $this->check('gd_webp', function_exists('imagewebp'), false, '');
        $checks[] = $this->check('dns', function_exists('dns_get_record'), false, '');

        foreach (['storage', 'storage/app', 'storage/app/private', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $path = $this->base.'/'.$dir;
            $checks[] = $this->check('writable:'.$dir, is_dir($path) && is_writable($path), true, $dir);
        }

        $media = $this->public.'/media';
        $checks[] = $this->check('writable:media', is_dir($media) && is_writable($media), true, 'public/media');

        $envWritable = is_file($this->base.'/.env') ? is_writable($this->base.'/.env') : is_writable($this->base);
        $checks[] = $this->check('writable:env', $envWritable, true, '.env');

        $checks[] = $this->check('vendor', $this->hasVendor(), true, 'vendor/autoload.php');
        $checks[] = $this->check('assets', $this->hasAssets(), true, 'build/manifest.json');

        // Release packages list every file they ship; an interrupted FTP upload shows up here.
        if (($missing = $this->missingPackageFiles()) !== null) {
            $checks[] = $this->check('complete', $missing === [], true, $missing === [] ? '' : '(✖ '.count($missing).': '.implode(', ', array_slice($missing, 0, 3)).(count($missing) > 3 ? ', …' : '').')');
        }

        $memory = $this->bytes((string) ini_get('memory_limit'));
        $checks[] = $this->check('memory', $memory < 0 || $memory >= 128 * 1024 * 1024, false, (string) ini_get('memory_limit'));
        $checks[] = $this->check('https', self::isHttps(), false, '');

        return $checks;
    }

    public function passes(): bool
    {
        foreach ($this->all() as $check) {
            if ($check['required'] && ! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /** vendor/ or public/build/ is missing: usually GitHub's "Source code" archive was uploaded instead of the release package. */
    public function isSourceArchive(): bool
    {
        return ! $this->hasVendor() || ! is_file($this->public.'/build/manifest.json');
    }

    /** Version of the uploaded code, read without booting the framework. */
    public function version(): string
    {
        preg_match("/'version' => '([^']+)'/", (string) @file_get_contents($this->base.'/config/biofy.php'), $m);

        return $m[1] ?? '';
    }

    private function hasVendor(): bool
    {
        return is_file($this->base.'/vendor/autoload.php') && is_file($this->base.'/vendor/laravel/framework/src/Illuminate/Foundation/Application.php');
    }

    /** The Vite manifest and every file it references. */
    private function hasAssets(): bool
    {
        $manifest = json_decode((string) @file_get_contents($this->public.'/build/manifest.json'), true);

        if (! is_array($manifest) || $manifest === []) {
            return false;
        }

        foreach ($manifest as $entry) {
            foreach (array_merge([$entry['file'] ?? ''], $entry['css'] ?? [], $entry['assets'] ?? []) as $file) {
                if ($file === '' || ! is_file($this->public.'/build/'.$file)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Files listed in release-manifest.json that are not on the server (null without a manifest,
     * e.g. in a development checkout). public/ files are looked up where public/ actually is.
     * Dot-files other than .htaccess are skipped: some FTP clients hide them and the application
     * does not need them.
     *
     * @return list<string>|null
     */
    public function missingPackageFiles(): ?array
    {
        $manifest = json_decode((string) @file_get_contents($this->base.'/release-manifest.json'), true);

        if (! is_array($manifest['files'] ?? null)) {
            return null;
        }

        $missing = [];

        foreach (array_keys($manifest['files']) as $file) {
            $name = basename($file);

            if ($name[0] === '.' && $name !== '.htaccess') {
                continue;
            }

            $path = str_starts_with($file, 'public/') ? $this->public.'/'.substr($file, 7) : $this->base.'/'.$file;

            if (! is_file($path)) {
                $missing[] = $file;
            }
        }

        return $missing;
    }

    public static function isHttps(): bool
    {
        return (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443)
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private function check(string $key, bool $ok, bool $required, string $detail): array
    {
        return ['key' => $key, 'ok' => $ok, 'required' => $required, 'detail' => $detail];
    }

    private function bytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
