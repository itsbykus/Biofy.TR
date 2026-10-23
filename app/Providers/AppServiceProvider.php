<?php

namespace App\Providers;

use App\Blocks\BlockRegistry;
use App\Models\User;
use App\Services\Analytics\AnalyticsRecorder;
use App\Services\Analytics\AnalyticsReporter;
use App\Services\AuditLogger;
use App\Services\Domains\DnsResolver;
use App\Services\Domains\DomainService;
use App\Services\Domains\HttpsProbe;
use App\Services\Domains\NativeDnsResolver;
use App\Services\Entitlements;
use App\Services\Mail\MailConfigurator;
use App\Services\SecurityLogger;
use App\Services\Settings;
use App\Services\SlugService;
use App\Themes\ThemeRegistry;
use App\Support\Permissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public array $singletons = [
        Settings::class => Settings::class,
        Entitlements::class => Entitlements::class,
        BlockRegistry::class => BlockRegistry::class,
        ThemeRegistry::class => ThemeRegistry::class,
        SlugService::class => SlugService::class,
        AuditLogger::class => AuditLogger::class,
        SecurityLogger::class => SecurityLogger::class,
        AnalyticsRecorder::class => AnalyticsRecorder::class,
        AnalyticsReporter::class => AnalyticsReporter::class,
        DomainService::class => DomainService::class,
        HttpsProbe::class => HttpsProbe::class,
        DnsResolver::class => NativeDnsResolver::class,
    ];

    public function boot(): void
    {
        // URLs in e-mails (password reset, verification) are always built from APP_URL,
        // never from the Host header of the incoming request (host header poisoning).
        if (! $this->app->runningUnitTests() && ($root = config('app.url'))) {
            URL::forceRootUrl($root);

            if (str_starts_with($root, 'https://')) {
                URL::forceScheme('https');
            }
        }

        Vite::useCspNonce();
        Paginator::useTailwind();

        $settings = $this->app->make(Settings::class);
        $this->applyRuntimeSettings($settings);

        Password::defaults(function () use ($settings) {
            $min = max(8, (int) ($settings->get('security.password_min_length') ?: 10));
            $rule = Password::min($min)->letters()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Superadmins pass every ability check; everyone else needs the explicit permission.
        Gate::before(fn (User $user) => $user->isSuperAdmin() && $user->isActive() ? true : null);

        // Every RBAC permission is also a Gate ability, so @can / authorize() work everywhere.
        foreach (array_keys(Permissions::ALL) as $permission) {
            Gate::define($permission, fn (User $user) => $user->isActive() && $user->hasPermission($permission));
        }

        $this->configureRateLimiting($settings);
    }

    private function applyRuntimeSettings(Settings $settings): void
    {
        $lifetime = (int) $settings->get('security.session_lifetime');

        if ($lifetime >= 5) {
            config(['session.lifetime' => $lifetime]);
        }

        config(['app.name' => (string) $settings->get('site.name')]);

        $this->app->make(MailConfigurator::class)->apply();

        if ($clientId = $settings->get('oauth.google.client_id')) {
            config([
                'services.google.client_id' => $clientId,
                'services.google.client_secret' => $settings->get('oauth.google.client_secret'),
                'services.google.redirect' => rtrim((string) config('app.url'), '/').'/auth/google/callback',
            ]);
        }
    }

    private function configureRateLimiting(Settings $settings): void
    {
        RateLimiter::for('login', function (Request $request) use ($settings) {
            $max = max(3, (int) $settings->get('security.login_max_attempts'));
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute($max)->by('login:'.$email.'|'.$request->ip()),
                Limit::perMinute($max * 4)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by('register:'.$request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('reset:'.Str::lower((string) $request->input('email'))),
            Limit::perHour(20)->by('reset-ip:'.$request->ip()),
        ]);
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by('2fa:'.$request->session()->get('login.id').'|'.$request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(5)->by('contact:'.$request->ip()));
        RateLimiter::for('domain-verify', fn (Request $request) => Limit::perMinute(4)->by('domain:'.$request->user()?->id));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by('upload:'.$request->user()?->id));
        RateLimiter::for('editor', fn (Request $request) => Limit::perMinute(240)->by('editor:'.$request->user()?->id));
        RateLimiter::for('cron', fn (Request $request) => Limit::perMinute(2)->by('cron'));
        RateLimiter::for('clicks', fn (Request $request) => Limit::perMinute(120)->by('click:'.$request->ip()));

        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(30)->by('api-guest:'.$request->ip());
            }

            $limit = app(Entitlements::class)->limit($user, Entitlements::API_RATE_LIMIT);
            $limit = $limit === Entitlements::UNLIMITED ? 600 : max(1, $limit);
            $tokenId = $user->currentAccessToken()?->id ?? 'session';

            // Per token and per user: one leaked token can't exhaust the whole account.
            return [
                Limit::perMinute($limit)->by('api-token:'.$tokenId),
                Limit::perMinute($limit * 2)->by('api-user:'.$user->id),
            ];
        });
    }
}
