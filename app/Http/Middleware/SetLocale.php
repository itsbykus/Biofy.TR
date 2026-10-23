<?php

namespace App\Http\Middleware;

use App\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/** Locale precedence: ?lang= > session > user preference > Accept-Language > site default. */
class SetLocale
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('biofy.locales');
        $requested = $request->query('lang');

        if (is_string($requested) && in_array($requested, $supported, true)) {
            $request->session()->put('locale', $requested);

            if ($user = $request->user()) {
                $user->forceFill(['locale' => $requested])->saveQuietly();
            }
        }

        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $request->getPreferredLanguage($supported)
            ?? $this->settings->get('site.default_locale');

        if (! in_array($locale, $supported, true)) {
            $locale = 'tr';
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    public static function fromHeaders(Request $request): string
    {
        $supported = config('biofy.locales');
        $locale = $request->getPreferredLanguage($supported);

        return in_array($locale, $supported, true) ? $locale : 'tr';
    }
}
