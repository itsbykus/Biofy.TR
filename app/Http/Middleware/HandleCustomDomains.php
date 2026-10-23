<?php

namespace App\Http\Middleware;

use App\Http\Controllers\CustomDomainController;
use App\Services\Domains\DomainService;
use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Host routing. The platform's own host(s) continue to the normal router. A
 * verified customer domain only ever serves its one profile (+ click redirects,
 * vCard, robots) and never the login/admin area, so no session or cookie is
 * created there. Any other Host header is rejected (host header attacks).
 */
class HandleCustomDomains
{
    public function __construct(
        private readonly DomainService $domains,
        private readonly Settings $settings,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if ($this->isPlatformHost($host)) {
            return $next($request);
        }

        // Responses below bypass the rest of the global stack, so security headers are applied here.
        return app(SecurityHeaders::class)->handle($request, fn (Request $r) => $this->serveCustomHost($r, $host));
    }

    private function serveCustomHost(Request $request, string $host): Response
    {
        $entry = $this->domains->lookup($host);

        if ($entry === null) {
            return response(view('errors.unknown-host'), 404);
        }

        // Ownership/HTTPS probe used by the verification step; answered even before activation.
        if ($request->path() === '.well-known/biofy-domain-check') {
            return response($entry['token'], 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
        }

        if ($entry['status'] !== 'verified' || $entry['profile_id'] === null) {
            return response(view('errors.domain-pending'), 404);
        }

        if ($this->settings->bool('domains.require_https')) {
            if (! $entry['secure']) {
                return response(view('errors.domain-pending'), 503, ['Retry-After' => '3600']);
            }

            if (! $request->isSecure()) {
                return redirect()->away('https://'.$host.$request->getRequestUri(), 301);
            }
        }

        return app(CustomDomainController::class)->handle($request, $entry);
    }

    private function isPlatformHost(string $host): bool
    {
        if (in_array($host, $this->domains->platformHosts(), true)) {
            return true;
        }

        // Local development / test runs.
        return ! app()->isProduction() && in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
