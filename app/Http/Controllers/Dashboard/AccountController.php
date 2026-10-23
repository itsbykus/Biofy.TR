<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogger;
use App\Services\Settings;
use App\Services\SlugService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function __construct(
        private readonly UserAccountService $accounts,
        private readonly SecurityLogger $security,
        private readonly SlugService $slugs,
        private readonly Settings $settings,
    ) {
    }

    public function edit(Request $request): View
    {
        return view('dashboard.account.edit', [
            'user' => $request->user(),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'graceDays' => $this->settings->int('privacy.account_deletion_grace_days'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['username' => strtolower(trim((string) $request->input('username'))), 'email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in(config('biofy.locales'))],
            'timezone' => ['nullable', 'timezone:all'],
            'current_password' => ['nullable', 'string'],
        ]);

        if ($data['username'] !== $user->username && ($problem = $this->slugs->problemWithUsername($data['username'], $user))) {
            throw ValidationException::withMessages(['username' => __($problem)]);
        }

        $emailChanged = $data['email'] !== $user->email;

        // Changing the login e-mail is sensitive: it requires the current password (when one exists).
        if ($emailChanged && $user->hasPassword() && ! $this->accounts->verifyPassword($user, $data['current_password'] ?? null)) {
            throw ValidationException::withMessages(['current_password' => __('auth.password')]);
        }

        $oldEmail = $user->email;
        $user->fill(collect($data)->only(['name', 'username', 'email', 'locale', 'timezone'])->all());

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();
        $request->session()->put('locale', $user->locale);

        if ($emailChanged) {
            $this->security->log($user, SecurityLogger::EMAIL_CHANGED, ['from_hash' => hash('sha256', $oldEmail)]);
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', $emailChanged ? __('account.updated_verify_email') : __('account.updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate([
            'current_password' => [$user->hasPassword() ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($user->hasPassword() && ! $this->accounts->verifyPassword($user, $request->input('current_password'))) {
            throw ValidationException::withMessages(['current_password' => __('auth.password')]);
        }

        $this->accounts->changePassword($user, (string) $request->input('password'));
        // Other devices are signed out; this one keeps a fresh session.
        $this->accounts->terminateSessions($user, $request->session()->getId());
        Auth::guard('web')->login($user->fresh());
        $request->session()->regenerate();

        return back()->with('success', __('account.password_updated'));
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $this->security->log($user, SecurityLogger::DATA_EXPORTED, ['type' => 'account_json']);
        $payload = $this->accounts->export($user);

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, 'biofy-data-'.$user->username.'-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json']);
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['confirm' => ['required', 'in:'.$user->username]], ['confirm.in' => __('account.delete_confirm_mismatch')]);

        $this->accounts->requestDeletion($user);
        $this->accounts->terminateSessions($user, $request->session()->getId());

        return redirect()->route('account.edit')->with('warning', __('account.deletion_requested'));
    }

    public function cancelDeletion(Request $request): RedirectResponse
    {
        $this->accounts->cancelDeletion($request->user());

        return redirect()->route('dashboard')->with('success', __('account.deletion_cancelled'));
    }
}
