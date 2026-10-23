<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLogger;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const CHALLENGE_TTL = 600;

    public function __construct(private readonly SecurityLogger $security)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pendingUser($request) ? view('auth.two-factor-challenge') : redirect()->route('login');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->with('error', __('auth.two_factor_expired'));
        }

        $request->validate([
            'code' => ['nullable', 'string', 'max:10'],
            'recovery_code' => ['nullable', 'string', 'max:30'],
        ]);

        if ($request->filled('recovery_code')) {
            if (! self::consumeRecoveryCode($user, (string) $request->input('recovery_code'))) {
                $this->security->log($user, SecurityLogger::TWO_FACTOR_FAILED, ['type' => 'recovery']);

                throw ValidationException::withMessages(['recovery_code' => __('auth.two_factor_invalid')]);
            }

            $this->security->log($user, SecurityLogger::RECOVERY_CODE_USED);
        } elseif (! self::verifyCode($user, (string) $request->input('code'))) {
            $this->security->log($user, SecurityLogger::TWO_FACTOR_FAILED, ['type' => 'totp']);

            throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid')]);
        }

        return LoginController::completeLogin($request, $user, (bool) $request->session()->get('login.remember'), $this->security, '2fa');
    }

    /** TOTP check with replay protection: a code's time-step can be used only once. */
    public static function verifyCode(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $step = Totp::verify($user->two_factor_secret, $code);

        if ($step === null) {
            return false;
        }

        return Cache::add('2fa-step:'.$user->id.':'.$step, true, 120);
    }

    public static function consumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(preg_replace('/\s+/', '', $code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.id');
        $started = (int) $request->session()->get('login.started_at');

        if (! $id || now()->getTimestamp() - $started > self::CHALLENGE_TTL) {
            return null;
        }

        $user = User::query()->find($id);

        return $user && ! $user->isSuspended() && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
