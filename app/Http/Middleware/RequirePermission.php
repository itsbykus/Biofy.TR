<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Backend authorisation for staff areas; the UI hiding a menu item is never enough. */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isActive(), 403);

        foreach ($permissions as $permission) {
            abort_unless($user->hasPermission($permission), 403);
        }

        return $next($request);
    }
}
