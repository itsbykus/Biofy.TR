<?php

namespace BiofyInstaller;

/** Builds the production .env file with safely quoted values. */
final class EnvWriter
{
    public static function generateKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    public static function quote(string $value): string
    {
        // Line breaks are never valid in these values; quotes and "$" are escaped (no variable expansion).
        $value = str_replace(["\r", "\n"], '', $value);

        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@+-]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    public static function build(array $values): string
    {
        $https = str_starts_with((string) $values['APP_URL'], 'https://');

        $env = [
            'APP_NAME' => $values['APP_NAME'],
            'APP_ENV' => 'production',
            'APP_KEY' => $values['APP_KEY'],
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim((string) $values['APP_URL'], '/'),
            'APP_LOCALE' => $values['APP_LOCALE'],
            'APP_FALLBACK_LOCALE' => 'tr',
            'APP_MAINTENANCE_DRIVER' => 'file',
            'BCRYPT_ROUNDS' => '12',
            'LOG_CHANNEL' => 'daily',
            'LOG_LEVEL' => 'warning',
            'LOG_DAILY_DAYS' => '14',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $values['DB_HOST'],
            'DB_PORT' => (string) $values['DB_PORT'],
            'DB_DATABASE' => $values['DB_DATABASE'],
            'DB_USERNAME' => $values['DB_USERNAME'],
            'DB_PASSWORD' => $values['DB_PASSWORD'],
            'DB_PREFIX' => $values['DB_PREFIX'] ?? '',
            'SESSION_DRIVER' => 'database',
            'SESSION_LIFETIME' => '120',
            'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false',
            'SESSION_SAME_SITE' => 'lax',
            'QUEUE_CONNECTION' => 'sync',
            'CACHE_STORE' => 'file',
            'FILESYSTEM_DISK' => 'local',
            'BROADCAST_CONNECTION' => 'log',
            'MAIL_MAILER' => 'log',
            'TRUSTED_PROXIES' => $values['TRUSTED_PROXIES'] ?? '',
            'BIOFY_RELEASE_PUBLIC_KEY' => $values['BIOFY_RELEASE_PUBLIC_KEY'] ?? '',
        ];

        $lines = ['# Written by the Biofy.TR installer on '.gmdate('Y-m-d H:i').' UTC. Keep this file private.'];

        foreach ($env as $key => $value) {
            $lines[] = $key.'='.self::quote((string) $value);
        }

        return implode("\n", $lines)."\n";
    }

    /** Writes atomically and restricts permissions where the host allows it. */
    public static function write(string $path, string $contents): bool
    {
        $tmp = $path.'.tmp-'.bin2hex(random_bytes(4));

        if (file_put_contents($tmp, $contents, LOCK_EX) === false) {
            return false;
        }

        @chmod($tmp, 0640);

        return rename($tmp, $path);
    }
}
