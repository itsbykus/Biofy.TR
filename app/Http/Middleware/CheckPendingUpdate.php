<?php

namespace App\Http\Middleware;

use App\Services\System\UpdateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After new files are uploaded by FTP but before the database is migrated, the
 * public site is paused (to avoid errors from a half-updated schema). Staff can
 * still sign in and run the update from the admin panel.
 */
class CheckPendingUpdate
{
    public function __construct(private readonly UpdateService $updates)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->updates->isPending()) {
            return $next($request);
        }

        if ($request->routeIs('login', 'login.*', 'logout', 'two-factor.*', 'admin.system.*', 'lang.switch')
            || $request->user()?->canAccessAdmin()) {
            return $next($request);
        }

        return response()->view('errors.updating', [], 503, ['Retry-After' => '600']);
    }
}
