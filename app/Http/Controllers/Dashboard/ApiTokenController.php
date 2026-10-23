<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Services\Entitlements;
use App\Services\SecurityLogger;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public const ABILITIES = ['profiles:read', 'profiles:write', 'links:read', 'links:write', 'analytics:read', 'domains:read'];

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly SecurityLogger $security,
        private readonly Settings $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.api.index', [
            'tokens' => $user->tokens()->latest()->get(),
            'abilities' => self::ABILITIES,
            'allowed' => $this->settings->bool('api.enabled') && $this->entitlements->allows($user, Entitlements::API_ACCESS),
            'limit' => $this->entitlements->limit($user, Entitlements::API_MAX_TOKENS),
            'rateLimit' => $this->entitlements->limit($user, Entitlements::API_RATE_LIMIT),
            'newToken' => $request->session()->get('api.new_token'),
            'recent' => ApiRequestLog::query()->where('user_id', $user->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(self::ABILITIES)],
            'expires_in_days' => ['nullable', 'integer', Rule::in([30, 90, 180, 365])],
        ]);

        if (! $this->entitlements->withinLimit($user, Entitlements::API_MAX_TOKENS, $user->tokens()->count())) {
            return back()->with('error', __('api.token_limit'));
        }

        $expires = isset($data['expires_in_days']) ? now()->addDays((int) $data['expires_in_days']) : null;
        $token = $user->createToken($data['name'], array_values(array_unique($data['abilities'])), $expires);
        $this->security->log($user, SecurityLogger::API_TOKEN_CREATED, ['name' => $data['name']]);

        // The plain token is shown once; only its SHA-256 hash is stored.
        return redirect()->route('api-tokens.index')->with('api.new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $tokenId): RedirectResponse
    {
        $user = $request->user();
        $token = $user->tokens()->whereKey($tokenId)->first();
        abort_if($token === null, 404);

        $token->delete();
        $this->security->log($user, SecurityLogger::API_TOKEN_REVOKED, ['name' => $token->name]);

        return back()->with('success', __('api.token_revoked'));
    }
}
