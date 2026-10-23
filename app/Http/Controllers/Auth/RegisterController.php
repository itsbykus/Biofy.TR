<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogger;
use App\Services\Settings;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private readonly Settings $settings,
        private readonly UserAccountService $accounts,
        private readonly SecurityLogger $security,
    ) {
    }

    public function create(): View
    {
        return view('auth.register', [
            'registrationEnabled' => $this->settings->bool('registration.enabled'),
            'googleEnabled' => $this->settings->bool('oauth.google.enabled') && $this->settings->get('oauth.google.client_id') && $this->settings->hasSecret('oauth.google.client_secret'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->settings->bool('registration.enabled'), 403, __('auth.registration_closed'));

        $request->merge(['username' => strtolower(trim((string) $request->input('username'))), 'email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = $this->accounts->register($data + ['locale' => app()->getLocale()]);

        return LoginController::completeLogin($request, $user, false, $this->security, 'registration');
    }
}
