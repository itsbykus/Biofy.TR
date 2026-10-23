<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use App\Services\Entitlements;
use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Runs after token authentication: platform switch, account status, plan feature, usage log. */
class ApiAccess
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Entitlements $entitlements,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->bool('api.enabled')) {
            return $this->error('unavailable', __('api.disabled'), 503);
        }

        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            return $this->error('forbidden', __('api.account_inactive'), 403);
        }

        if (! $this->entitlements->allows($user, Entitlements::API_ACCESS)) {
            return $this->error('feature_unavailable', __('plans.feature_unavailable'), 403);
        }

        $started = microtime(true);
        $response = $next($request);

        try {
            ApiRequestLog::query()->create([
                'user_id' => $user->id,
                'token_id' => $user->currentAccessToken()?->id,
                'method' => $request->method(),
                'path' => Str::limit('/'.$request->path(), 250, ''),
                'status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Logging must not break the API response.
        }

        return $response;
    }

    private function error(string $code, string $message, int $status): Response
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
