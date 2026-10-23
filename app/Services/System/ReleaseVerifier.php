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
     * @return array{status: string, version: ?string, missing: list<string>, modified: list<string>, checked: int}
     *   status: ok | unsigned | bad_signature | no_manifest | files_mismatch
     */
    public function verify(int $maxSeconds = 60): array
    {
        $result = ['status' => 'no_manifest', 'version' => null, 'missing' => [], 'modified' => [], 'checked' => 0];
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

        $deadline = microtime(true) + $maxSeconds;

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

        if ($result['status'] !== 'unsigned') {
            $result['status'] = ($result['missing'] || $result['modified']) ? 'files_mismatch' : 'ok';
        } elseif ($result['missing'] || $result['modified']) {
            $result['status'] = 'files_mismatch';
        }

        $result['missing'] = array_slice($result['missing'], 0, 50);
        $result['modified'] = array_slice($result['modified'], 0, 50);

        return $result;
    }
}
