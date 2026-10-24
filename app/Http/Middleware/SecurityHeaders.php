<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use App\Support\Embeds;
use App\Support\TrackingContext;
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
        // Fresh per request: views add the tag providers they render (see partials/tracking).
        app()->instance(TrackingContext::class, new TrackingContext);

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
        $frames = $request->attributes->get('profile_page') ? array_map(fn ($h) => 'https://'.$h, Embeds::frameHosts()) : ["'self'"];
        // Consented measurement tags (Google / Meta / TikTok) on pages that use them.
        $extra = app(TrackingContext::class)->cspAdditions();
        $with = fn (string $directive, array $base) => $directive.' '.implode(' ', array_unique(array_merge($base, $extra[$directive] ?? [])));

        return implode('; ', [
            "default-src 'self'",
            $with('script-src', ["'self'", "'nonce-{$nonce}'"]),
            "style-src 'self' 'nonce-{$nonce}'",
            $with('img-src', ["'self'", 'data:', 'https://i.ytimg.com']),
            "font-src 'self' data:",
            $with('connect-src', ["'self'"]),
            "media-src 'self'",
            $with('frame-src', $frames),
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
