<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SecurityLogger;
use App\Services\Settings;
use App\Services\SlugService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google sign-in (OAuth 2.0 / OpenID Connect via Socialite).
 * Account safety rules:
 *  - only Google accounts with a verified e-mail are accepted;
 *  - a Google identity is matched by its stable subject id, never by e-mail alone;
 *  - if a local account already uses that e-mail, nothing is merged automatically:
 *    the owner must sign in with their password and link Google from Security.
 */
class GoogleController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly SecurityLogger $security,
        private readonly UserAccountService $accounts,
        private readonly SlugService $slugs,
    ) {
    }

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), 404);

        $intent = in_array($request->query('intent'), ['link', 'confirm'], true) && $request->user() ? $request->query('intent') : 'login';
        $request->session()->put('google.intent', $intent);

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), 404);
        $intent = $request->session()->pull('google.intent', 'login');

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            Log::notice('Google OAuth callback failed: '.class_basename($e));

            return redirect()->route($request->user() ? 'security.index' : 'login')->with('error', __('auth.google_failed'));
        }

        $raw = $google->getRaw();
        $emailVerified = filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $googleId = (string) $google->getId();
        $email = Str::lower((string) $google->getEmail());

        if ($googleId === '' || $email === '' || ! $emailVerified) {
            return redirect()->route('login')->with('error', __('auth.google_unverified'));
        }

        $linked = SocialAccount::query()->where('provider', 'google')->where('provider_user_id', $googleId)->first();

        if ($intent === 'link' && ($current = $request->user())) {
            if ($linked && $linked->user_id !== $current->id) {
                return redirect()->route('security.index')->with('error', __('auth.google_linked_elsewhere'));
            }

            SocialAccount::query()->updateOrCreate(
                ['user_id' => $current->id, 'provider' => 'google'],
                ['provider_user_id' => $googleId, 'provider_email' => $email],
            );
            $this->security->log($current, SecurityLogger::GOOGLE_LINKED);

            return redirect()->route('security.index')->with('success', __('auth.google_linked'));
        }

        if ($intent === 'confirm' && ($current = $request->user())) {
            if ($linked && $linked->user_id === $current->id) {
                $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());

                return redirect()->intended(route('dashboard'));
            }

            return redirect()->route('password.confirm')->with('error', __('auth.google_confirm_mismatch'));
        }

        if ($linked) {
            $user = $linked->user;

            if ($user->isSuspended()) {
                return redirect()->route('login')->with('error', __('auth.suspended'));
            }

            if ($user->hasTwoFactorEnabled()) {
                $request->session()->regenerate();
                $request->session()->put(['login.id' => $user->id, 'login.remember' => false, 'login.started_at' => now()->getTimestamp()]);

                return redirect()->route('two-factor.login');
            }

            return LoginController::completeLogin($request, $user, false, $this->security, 'google');
        }

        if (User::query()->where('email', $email)->exists()) {
            return redirect()->route('login')->with('error', __('auth.google_email_exists'));
        }

        if (! $this->settings->bool('registration.enabled')) {
            return redirect()->route('login')->with('error', __('auth.registration_closed'));
        }

        $request->session()->put('google.pending', [
            'id' => $googleId,
            'email' => $email,
            'name' => Str::limit((string) ($google->getName() ?: Str::before($email, '@')), 120, ''),
            'at' => now()->getTimestamp(),
        ]);

        return redirect()->route('auth.google.complete');
    }

    /** New Google users pick their username and accept the terms before the account is created. */
    public function complete(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.google-complete', [
            'pending' => $pending,
            'suggestion' => $this->slugs->suggest(Str::before($pending['email'], '@')),
        ]);
    }

    public function finish(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('register');
        }

        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30'],
            'terms' => ['accepted'],
        ]);

        if (User::query()->where('email', $pending['email'])->exists()
            || SocialAccount::query()->where('provider', 'google')->where('provider_user_id', $pending['id'])->exists()) {
            $request->session()->forget('google.pending');

            return redirect()->route('login')->with('error', __('auth.google_email_exists'));
        }

        // Google confirmed the address, so the account starts verified.
        $user = $this->accounts->register([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $pending['email'],
            'locale' => app()->getLocale(),
        ], now()->toDateTimeString());

        SocialAccount::query()->create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => $pending['id'], 'provider_email' => $pending['email']]);
        $request->session()->forget('google.pending');

        return LoginController::completeLogin($request, $user, false, $this->security, 'google');
    }

    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('google.pending');

        return is_array($pending) && now()->getTimestamp() - (int) $pending['at'] < 900 ? $pending : null;
    }

    private function enabled(): bool
    {
        return $this->settings->bool('oauth.google.enabled')
            && $this->settings->get('oauth.google.client_id')
            && $this->settings->hasSecret('oauth.google.client_secret');
    }
}
