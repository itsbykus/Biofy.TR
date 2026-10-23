<?php

namespace App\Services\Domains;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Confirms the hostname serves this installation over valid HTTPS by fetching a
 * token from /.well-known/biofy-domain-check with full certificate verification.
 * Certificates themselves are issued by the hosting provider, not by this app.
 */
class HttpsProbe
{
    /** @return array{0: bool, 1: string} passed, details */
    public function check(string $hostname, string $expectedToken): array
    {
        try {
            $response = Http::timeout(8)->connectTimeout(5)->withoutRedirecting()
                ->withHeaders(['User-Agent' => 'BiofyDomainCheck/1.0'])
                ->get('https://'.$hostname.'/.well-known/biofy-domain-check');

            if (! $response->successful()) {
                return [false, 'http_error'];
            }

            return hash_equals($expectedToken, trim($response->body()))
                ? [true, 'ok']
                : [false, 'token_mismatch'];
        } catch (Throwable $e) {
            $message = $e->getMessage();

            return [false, str_contains(strtolower($message), 'ssl') || str_contains(strtolower($message), 'certificate') ? 'certificate_error' : 'unreachable'];
        }
    }
}
