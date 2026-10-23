<?php

use App\Exceptions\EditConflictException;
use App\Exceptions\UserFacingException;
use App\Http\Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (MiddlewareConfig $middleware): void {
        // Behind Cloudflare / a load balancer set TRUSTED_PROXIES=* (or a CIDR list) in .env.
        $proxies = env('TRUSTED_PROXIES');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : ($proxies ? array_map('trim', explode(',', $proxies)) : null));

        // Requests for customer domains are answered before sessions/cookies are started.
        $middleware->prepend(Middleware\HandleCustomDomains::class);
        $middleware->append(Middleware\SecurityHeaders::class);

        $middleware->web(append: [
            Middleware\SetLocale::class,
            Middleware\CheckPendingUpdate::class,
            Middleware\PlatformMaintenance::class,
            Middleware\EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'password.confirm' => Middleware\ConfirmIdentity::class,
            'api.access' => Middleware\ApiAccess::class,
            'permission' => Middleware\RequirePermission::class,
            'verified.optional' => Middleware\EnsureEmailIsVerifiedWhenRequired::class,
            'admin.2fa' => Middleware\RequireTwoFactorForStaff::class,
            'feature' => Middleware\RequireFeature::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // The URL cron endpoint authenticates with its own secret token.
        $middleware->validateCsrfTokens(except: ['cron/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never log secrets that might be part of a request.
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'client_secret', 'smtp_password', 'code', 'recovery_code', 'install_key']);

        $exceptions->render(function (UserFacingException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => ['code' => 'unprocessable', 'message' => $e->getMessage()]], 422);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->render(function (EditConflictException $e, Request $request) {
            $message = __('editor.conflict');

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'conflict' => true], 409)
                : back()->with('error', $message);
        });

        // Laravel converts TokenMismatchException into a 419 HttpException before render callbacks run.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $e->getPrevious() instanceof TokenMismatchException && ! $request->expectsJson()) {
                return redirect()->back()->withInput($request->except(['password', 'password_confirmation', '_token']))->with('error', __('errors.session_expired'));
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException, $e instanceof \Laravel\Sanctum\Exceptions\MissingAbilityException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException, $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 404,
                $e instanceof \Illuminate\Validation\ValidationException => 422,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $code = match ($status) {
                401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found', 405 => 'method_not_allowed',
                422 => 'validation_failed', 429 => 'rate_limited', 503 => 'unavailable', default => 'server_error',
            };

            $body = ['error' => ['code' => $code, 'message' => $status === 500 ? __('errors.server') : ($e->getMessage() ?: __('errors.http_'.$status))]];

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $body['error']['message'] = __('errors.validation');
                $body['error']['details'] = $e->errors();
            }

            $headers = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json($body, $status, $headers);
        });
    })->create();

// When public/ was copied elsewhere (e.g. public_html), the installer records its
// location so CLI commands (cron, backups) use the same public path as the web.
if (is_file(__DIR__.'/public-path.php')) {
    $publicPath = require __DIR__.'/public-path.php';

    if (is_string($publicPath) && is_dir($publicPath)) {
        $app->usePublicPath($publicPath);
    }
}

return $app;
