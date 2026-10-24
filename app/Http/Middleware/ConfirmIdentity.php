<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Re-authentication gate for sensitive actions (replaces Laravel's RequirePassword).
 * A form submitted while the confirmation has expired is kept (encrypted, for a few
 * minutes) and sent again once the user has confirmed, so the action is not lost.
 * Requests with file uploads cannot be replayed: the user goes back to the form.
 */
class ConfirmIdentity
{
    private const PENDING_KEY = 'auth.pending_action';

    private const PENDING_TTL = 600;

    public function handle(Request $request, Closure $next): Response
    {
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        if (time() - $confirmedAt < self::timeoutSeconds()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.confirm_required')], 423);
        }

        $request->session()->forget(self::PENDING_KEY);

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        } else {
            $request->session()->put('url.intended', url()->previous());

            $pending = $request->allFiles() ? false : json_encode([
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => Arr::except($request->input(), ['_token', '_method']),
                'at' => time(),
            ]);

            // Uploads, or input that can't be stored (invalid UTF-8): the user repeats the action.
            if (is_string($pending)) {
                $request->session()->put(self::PENDING_KEY, Crypt::encryptString($pending));
            } else {
                $request->session()->put('auth.confirm_repeat', true);
            }
        }

        return redirect()->route('password.confirm');
    }

    public static function timeoutSeconds(): int
    {
        try {
            return 60 * max(5, min(240, app(Settings::class)->int('security.confirm_timeout_minutes') ?: 30));
        } catch (Throwable) {
            return 1800;
        }
    }

    /**
     * After a successful confirmation: a page that re-sends the pending form, or
     * the page the user came from (with a hint when the action must be repeated).
     */
    public static function afterConfirmation(Request $request): Response
    {
        $request->session()->put('auth.password_confirmed_at', now()->getTimestamp());
        $pending = $request->session()->pull(self::PENDING_KEY);

        if (is_string($pending)) {
            try {
                $action = json_decode(Crypt::decryptString($pending), true, 32, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $action = null;
            }

            // Only same-site URLs and a short window; anything else falls through to a normal redirect.
            if (is_array($action) && time() - (int) ($action['at'] ?? 0) < self::PENDING_TTL
                && str_starts_with((string) $action['url'], rtrim($request->root(), '/').'/')) {
                // The form is re-sent without a Referer (see the view), so "back()" in the target
                // controller returns to the page that held the form, not to the confirmation page.
                $request->session()->setPreviousUrl((string) $request->session()->pull('url.intended', url('/')));

                return response()->view('auth.resume-action', [
                    'url' => $action['url'],
                    'method' => strtoupper((string) $action['method']),
                    'fields' => self::flatten((array) ($action['input'] ?? [])),
                ]);
            }
        }

        $redirect = redirect()->intended(route('dashboard'));

        return $request->session()->pull('auth.confirm_repeat') ? $redirect->with('status', __('auth.confirm_repeat')) : $redirect;
    }

    /** ['a' => ['tr' => 'x']] -> [['a[tr]', 'x']] for hidden form fields. */
    private static function flatten(array $input, string $prefix = ''): array
    {
        $fields = [];

        foreach ($input as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';

            if (is_array($value)) {
                $fields = array_merge($fields, self::flatten($value, $name));
            } else {
                $fields[] = [$name, is_bool($value) ? (int) $value : (string) $value];
            }
        }

        return $fields;
    }
}
