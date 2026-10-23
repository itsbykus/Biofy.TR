<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Services\QrCodeService;
use App\Services\SecurityLogger;
use App\Services\Settings;
use App\Services\UserAccountService;
use App\Support\Totp;
use App\Support\UserAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(
        private readonly SecurityLogger $security,
        private readonly UserAccountService $accounts,
        private readonly Settings $settings,
    ) {
    }

    public function index(Request $request, QrCodeService $qr): View
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get('2fa.pending_secret');
        $sessions = collect();

        if (config('session.driver') === 'database') {
            $sessions = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->orderByDesc('last_activity')->get()
                ->map(fn ($s) => [
                    'id_hash' => hash('sha256', $s->id),
                    'current' => $s->id === $request->session()->getId(),
                    'ip' => $s->ip_address,
                    'agent' => UserAgent::describe($s->user_agent),
                    'device' => UserAgent::device($s->user_agent),
                    'last_active' => \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity),
                ]);
        }

        return view('dashboard.security.index', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'pendingQr' => $pendingSecret ? $qr->svg(Totp::provisioningUri($pendingSecret, $user->email, (string) $this->settings->get('site.name')), 220) : null,
            'recoveryCodes' => $request->session()->get('2fa.recovery_codes_plain'),
            'sessions' => $sessions,
            'events' => SecurityEvent::query()->where('user_id', $user->id)->latest('id')->limit(30)->get(),
            'googleLinked' => $user->socialAccounts()->where('provider', 'google')->first(),
            'googleEnabled' => $this->settings->bool('oauth.google.enabled') && $this->settings->hasSecret('oauth.google.client_secret'),
        ]);
    }

    public function startTwoFactor(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasTwoFactorEnabled(), 409);
        $request->session()->put('2fa.pending_secret', Totp::generateSecret());

        return redirect()->route('security.index')->withFragment('two-factor');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('2fa.pending_secret');
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        if (! $secret || Totp::verify($secret, (string) $request->input('code')) === null) {
            throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid')]);
        }

        $plain = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain),
        ])->save();

        $request->session()->forget('2fa.pending_secret');
        // Shown exactly once, on the next page view.
        $request->session()->flash('2fa.recovery_codes_plain', $plain);
        $this->security->log($user, SecurityLogger::TWO_FACTOR_ENABLED);
        $this->accounts->notifySecurity($user, 'two_factor_enabled');

        return redirect()->route('security.index')->with('success', __('security.two_factor_enabled'))->withFragment('two-factor');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 409);

        $plain = $this->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain)])->save();
        $request->session()->flash('2fa.recovery_codes_plain', $plain);

        return redirect()->route('security.index')->with('success', __('security.recovery_regenerated'))->withFragment('two-factor');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        $this->security->log($user, SecurityLogger::TWO_FACTOR_DISABLED);
        $this->accounts->notifySecurity($user, 'two_factor_disabled');

        return redirect()->route('security.index')->with('success', __('security.two_factor_disabled'));
    }

    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $user = $request->user();
        $count = $this->accounts->terminateSessions($user, $request->session()->getId());
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        $this->security->log($user, SecurityLogger::LOGOUT_OTHER_SESSIONS, ['count' => $count]);

        return back()->with('success', trans_choice('security.sessions_terminated', $count, ['count' => $count]));
    }

    public function destroySession(Request $request, string $idHash): RedirectResponse
    {
        $user = $request->user();
        $session = DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->get(['id'])
            ->first(fn ($s) => hash_equals(hash('sha256', $s->id), $idHash));

        abort_if($session === null || $session->id === $request->session()->getId(), 404);
        DB::table(config('session.table', 'sessions'))->where('id', $session->id)->where('user_id', $user->id)->delete();

        return back()->with('success', trans_choice('security.sessions_terminated', 1, ['count' => 1]));
    }

    public function unlinkGoogle(Request $request): RedirectResponse
    {
        $this->accounts->unlinkGoogle($request->user());

        return back()->with('success', __('auth.google_unlinked'));
    }

    /** @return list<string> */
    private function generateRecoveryCodes(): array
    {
        return array_map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)), range(1, 8));
    }
}
