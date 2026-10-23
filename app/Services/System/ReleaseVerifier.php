<?php

namespace App\Services\System;

/**
 * Verifies the files uploaded by FTP against the signed release manifest:
 *   release-manifest.json  {"version": "...", "files": {"path": "sha256", ...}}
 *   release-manifest.sig   base64 Ed25519 signature of the manifest bytes
 * The public key is pinned in config/biofy.php; the private key never ships.
 */
class ReleaseVerifier
{
    public function manifestPath(): string
    {
        return base_path('release-manifest.json');
    }

    /**
     * @return array{status: string, version: ?string, missing: list<string>, modified: list<string>, checked: int, total: int}
     *   status: ok | unsigned | bad_signature | no_manifest | files_mismatch | incomplete
     *   incomplete = the time budget ran out before every file was hashed (never reported as ok).
     */
    public function verify(int $maxSeconds = 240): array
    {
        $result = ['status' => 'no_manifest', 'version' => null, 'missing' => [], 'modified' => [], 'checked' => 0, 'total' => 0];
        $manifestPath = $this->manifestPath();

        if (! is_file($manifestPath)) {
            return $result;
        }

        $raw = (string) file_get_contents($manifestPath);
        $manifest = json_decode($raw, true);
        $result['version'] = $manifest['version'] ?? null;

        $publicKey = (string) config('biofy.release_public_key');
        $signaturePath = base_path('release-manifest.sig');

        if ($publicKey === '' || ! is_file($signaturePath) || ! function_exists('sodium_crypto_sign_verify_detached')) {
            $result['status'] = 'unsigned';
        } else {
            $signature = base64_decode(trim((string) file_get_contents($signaturePath)), true);
            $key = base64_decode($publicKey, true);
            $valid = $signature !== false && $key !== false
                && strlen($signature) === SODIUM_CRYPTO_SIGN_BYTES && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                && sodium_crypto_sign_verify_detached($signature, $raw, $key);

            if (! $valid) {
                $result['status'] = 'bad_signature';

                return $result;
            }
        }

        // Hashing ~8,000 files can exceed a 30 s max_execution_time on slow shared disks: extend it where
        // the host allows, and stop a few seconds before the effective limit instead of dying mid-request.
        $limit = (int) ini_get('max_execution_time');

        // Only ever raise it: 0 means unlimited (CLI), and set_time_limit() restarts the clock.
        if ($limit > 0 && $limit < $maxSeconds + 30 && function_exists('set_time_limit') && @set_time_limit($maxSeconds + 30)) {
            $limit = 0; // the new limit counts from now, well past the deadline below
        }
        $deadline = microtime(true) + $maxSeconds;

        if ($limit > 0) {
            $deadline = min($deadline, (defined('LARAVEL_START') ? LARAVEL_START : microtime(true)) + $limit - 5);
        }

        $result['total'] = count($manifest['files'] ?? []);

        foreach ($manifest['files'] ?? [] as $relative => $hash) {
            if (str_contains($relative, '..')) {
                continue;
            }

            $path = base_path($relative);

            if (! is_file($path)) {
                $result['missing'][] = $relative;
            } elseif (! hash_equals($hash, (string) hash_file('sha256', $path))) {
                $result['modified'][] = $relative;
            }

            $result['checked']++;

            if (microtime(true) > $deadline) {
                break;
            }
        }

        if ($result['missing'] || $result['modified']) {
            $result['status'] = 'files_mismatch';
        } elseif ($result['checked'] < $result['total']) {
            $result['status'] = 'incomplete';
        } elseif ($result['status'] !== 'unsigned') {
            $result['status'] = 'ok';
        }

        $result['missing'] = array_slice($result['missing'], 0, 50);
        $result['modified'] = array_slice($result['modified'], 0, 50);

        return $result;
    }
}
