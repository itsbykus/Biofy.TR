<?php

namespace BiofyInstaller;

use PDO;
use PDOException;

/** Database connection test used by the installer (before Laravel is configured). */
final class Database
{
    /**
     * @return array{ok: bool, error: ?string, server: ?string}
     *   error: connection | version | not_empty | invalid_prefix
     */
    public static function test(array $c): array
    {
        if (! preg_match('/^[A-Za-z0-9_]{0,20}$/', (string) ($c['prefix'] ?? ''))) {
            return ['ok' => false, 'error' => 'invalid_prefix', 'server' => null];
        }

        try {
            $pdo = self::connect($c);
        } catch (PDOException $e) {
            // The driver message is shown (it never contains the password) to help fix host/port/user typos.
            return ['ok' => false, 'error' => 'connection', 'server' => self::cleanMessage($e->getMessage())];
        }

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $isMaria = stripos($version, 'mariadb') !== false;
        $number = preg_replace('/^(\d+\.\d+\.\d+).*/', '$1', $version);
        $supported = $isMaria ? version_compare($number, '10.3.0', '>=') : version_compare($number, '5.7.0', '>=');

        if (! $supported) {
            return ['ok' => false, 'error' => 'version', 'server' => $version];
        }

        // Refuse a database that already holds tables with this prefix (never overwrite data).
        $prefix = (string) ($c['prefix'] ?? '');
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?');
        $stmt->execute([str_replace('_', '\\_', $prefix).'%']);

        if ((int) $stmt->fetchColumn() > 0 && in_array($prefix.'users', self::tables($pdo), true)) {
            return ['ok' => false, 'error' => 'not_empty', 'server' => $version];
        }

        return ['ok' => true, 'error' => null, 'server' => $version];
    }

    public static function connect(array $c): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int) $c['port'], $c['database']);

        return new PDO($dsn, (string) $c['username'], (string) $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    private static function tables(PDO $pdo): array
    {
        return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function cleanMessage(string $message): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', $message), 0, 240);
    }
}
