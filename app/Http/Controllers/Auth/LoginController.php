<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLogger;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly SecurityLogger $security,
        private readonly Settings $settings,
    ) {
    }

    public function create(): View
    {
        return view('auth.login', [
            'googleEnabled' => $this->googleEnabled(),
            'registrationEnabled' => $this->settings->bool('registration.enabled'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:500'],
        ]);

        $user = User::query()->where('email', Str::lower($credentials['email']))->first();

        // Same error and similar timing whether or not the account exists (no user enumeration).
        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            if (! $user) {
                Hash::check($credentials['password'], Hash::make(Str::random(20)));
            } else {
                $this->security->log($user, SecurityLogger::LOGIN_FAILED);
            }

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if ($user->isSuspended()) {
            throw ValidationException::withMessages(['email' => __('auth.suspended')]);
        }

        $remember = $request->boolean('remember');

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put(['login.id' => $user->id, 'login.remember' => $remember, 'login.started_at' => now()->getTimestamp()]);

            return redirect()->route('two-factor.login');
        }

        return self::completeLogin($request, $user, $remember, $this->security);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            $this->security->log($user, SecurityLogger::LOGOUT);
        }

        return redirect()->route('home');
    }

    /** Final step shared by password, 2FA and Google sign-in. */
    public static function completeLogin(Request $request, User $user, bool $remember, SecurityLogger $security, string $method = 'password'): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);

        // New session id after authentication prevents session fixation.
        $request->session()->regenerate();
        $request->session()->forget(['login.id', 'login.remember', 'login.started_at']);
        $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());

        if ($user->locale) {
            $request->session()->put('locale', $user->preferredLocale());
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $security->log($user, SecurityLogger::LOGIN, ['method' => $method]);

        return redirect()->intended($user->canAccessAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    private function googleEnabled(): bool
    {
        return $this->settings->bool('oauth.google.enabled')
            && $this->settings->get('oauth.google.client_id')
            && $this->settings->hasSecret('oauth.google.client_secret');
    }
}
