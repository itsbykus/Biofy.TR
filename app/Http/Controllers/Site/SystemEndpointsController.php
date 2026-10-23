<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use App\Services\System\HealthCheck;
use App\Services\System\MaintenanceRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemEndpointsController extends Controller
{
    /** Language switcher: remembers the choice in the session (and on the account). */
    public function locale(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('biofy.locales'), true), 404);
        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->saveQuietly();

        // Only same-site relative redirects (no open redirect via Referer).
        $back = url()->previous();
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return parse_url($back, PHP_URL_HOST) === $appHost || parse_url($back, PHP_URL_HOST) === $request->getHost()
            ? redirect()->to($back)
            : redirect()->route('home');
    }

    /** Public health endpoint: status only. Details are in the admin panel. */
    public function health(HealthCheck $health): JsonResponse
    {
        $checks = $health->basic();
        $ok = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json(['status' => $ok ? 'ok' : 'degraded', 'version' => config('biofy.version')], $ok ? 200 : 503);
    }

    /** "URL cron" for hosts without a real crontab: GET /cron/{token}. */
    public function cron(string $token, Settings $settings, MaintenanceRunner $runner): JsonResponse
    {
        $expected = (string) $settings->get('cron.token');

        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        return response()->json(['ok' => true, 'report' => $runner->run()]);
    }
}
