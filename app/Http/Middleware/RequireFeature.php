<?php

namespace App\Http\Middleware;

use App\Services\Entitlements;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route-level feature gate (e.g. feature:analytics.export). */
class RequireFeature
{
    public function __construct(private readonly Entitlements $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user || ! $this->entitlements->allows($user, $feature)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => ['code' => 'feature_unavailable', 'message' => __('plans.feature_unavailable')]], 403);
            }

            return redirect()->route('plan.show')->with('warning', __('plans.feature_unavailable'));
        }

        return $next($request);
    }
}
