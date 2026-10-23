<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension takes effect on the very next request (even with "remember me").
 * Accounts with a pending deletion can only reach the page that cancels it.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', __('auth.suspended'));
        }

        if ($user->deletion_requested_at !== null && ! $request->routeIs('account.*', 'logout', 'lang.switch')) {
            return redirect()->route('account.edit')->with('warning', __('account.deletion_pending_notice'));
        }

        return $next($request);
    }
}
