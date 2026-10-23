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

        $checks[] = $this->check('vendor', is_file($this->base.'/vendor/autoload.php'), true, 'vendor/autoload.php');
        $checks[] = $this->check('assets', is_file($this->public.'/build/manifest.json'), true, 'build/manifest.json');

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
