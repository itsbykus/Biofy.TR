<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedWhenRequired
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail() && $this->settings->bool('registration.require_email_verification')) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.verify_required')], 403)
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
