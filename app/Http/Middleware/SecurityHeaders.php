<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use App\Support\Embeds;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && $this->settings->bool('security.hsts')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $contentType = (string) $headers->get('Content-Type');

        if (($contentType === '' || str_contains($contentType, 'text/html')) && ! Vite::isRunningHot()) {
            $headers->set('Content-Security-Policy', $this->policy($request));
        }

        return $response;
    }

    private function policy(Request $request): string
    {
        $nonce = Vite::cspNonce();
        // Only public profile pages may frame the allow-listed media players.
        $frames = $request->attributes->get('profile_page') ? implode(' ', array_map(fn ($h) => 'https://'.$h, Embeds::frameHosts())) : "'self'";

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data: https://i.ytimg.com",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'",
            "frame-src {$frames}",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
