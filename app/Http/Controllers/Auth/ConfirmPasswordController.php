<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Re-authentication before sensitive actions (Laravel's `password.confirm`
 * middleware). Accounts without a local password confirm with a 2FA code or by
 * signing in with Google again.
 */
class ConfirmPasswordController extends Controller
{
    public function __construct(private readonly UserAccountService $accounts)
    {
    }

    public function show(Request $request): View
    {
        return view('auth.confirm-password', ['user' => $request->user()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasPassword()) {
            $request->validate(['password' => ['required', 'string']]);

            if (! $this->accounts->verifyPassword($user, $request->input('password'))) {
                throw ValidationException::withMessages(['password' => __('auth.password')]);
            }
        } elseif ($user->hasTwoFactorEnabled()) {
            $request->validate(['code' => ['required', 'string']]);

            if (! TwoFactorChallengeController::verifyCode($user, (string) $request->input('code'))) {
                throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid')]);
            }
        } else {
            return redirect()->route('auth.google.redirect', ['intent' => 'confirm']);
        }

        $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());

        return redirect()->intended(route('dashboard'));
    }
}
