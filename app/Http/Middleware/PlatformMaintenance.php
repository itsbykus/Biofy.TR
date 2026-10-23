<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Maintenance mode controlled from the admin panel (no artisan/SSH needed). */
class PlatformMaintenance
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->bool('maintenance.enabled')) {
            return $next($request);
        }

        $allowed = $request->routeIs('login', 'login.*', 'logout', 'two-factor.*', 'lang.switch', 'health')
            || $request->user()?->canAccessAdmin()
            || ($this->settings->bool('maintenance.keep_profiles_online') && $request->routeIs('profile.*'));

        if ($allowed) {
            return $next($request);
        }

        return response()->view('errors.maintenance', ['message' => $this->settings->text('maintenance.message')], 503, ['Retry-After' => '1800']);
    }
}
