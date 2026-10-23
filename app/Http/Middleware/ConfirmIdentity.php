<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-authentication gate for sensitive actions (replaces Laravel's RequirePassword).
 * For non-GET requests the user is sent back to the page holding the form after
 * confirming, instead of being redirected to a POST-only URL.
 */
class ConfirmIdentity
{
    public const TIMEOUT_SECONDS = 900;

    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt < self::TIMEOUT_SECONDS) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.confirm_required')], 423);
        }

        $request->session()->put('url.intended', $request->isMethod('GET') ? $request->fullUrl() : url()->previous());

        return redirect()->route('password.confirm');
    }
}
