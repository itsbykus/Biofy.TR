<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [Site\SiteController::class, 'home'])->name('home');
Route::get('/features', [Site\SiteController::class, 'features'])->name('features');
Route::get('/themes', [Site\SiteController::class, 'themes'])->name('themes');
Route::get('/pricing', [Site\SiteController::class, 'pricing'])->name('pricing');
Route::get('/contact', [Site\ContactController::class, 'show'])->name('contact');
Route::post('/contact', [Site\ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

foreach (['about', 'help', 'privacy', 'terms', 'cookies', 'kvkk'] as $systemPage) {
    Route::get('/'.$systemPage, [Site\SiteController::class, 'page'])->defaults('slug', $systemPage)->name('page.'.$systemPage);
}
Route::get('/page/{slug}', [Site\SiteController::class, 'page'])->where('slug', '[a-z0-9-]+')->name('page.show');

// Not "/lang": when the whole package sits in the web root (shared hosting layout C), a path that
// starts with an application folder name is refused by that folder's .htaccess before routing.
Route::get('/locale/{locale}', [Site\SystemEndpointsController::class, 'locale'])->name('lang.switch');
Route::get('/robots.txt', [Site\SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [Site\SeoController::class, 'index'])->name('sitemap');
Route::get('/sitemap-pages.xml', [Site\SeoController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-profiles-{chunk}.xml', [Site\SeoController::class, 'profiles'])->whereNumber('chunk')->name('sitemap.profiles');
Route::get('/health', [Site\SystemEndpointsController::class, 'health'])->name('health');
Route::get('/cron/{token}', [Site\SystemEndpointsController::class, 'cron'])->middleware('throttle:cron')->name('cron');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');
    Route::get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
    Route::get('/two-factor-challenge', [Auth\TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [Auth\TwoFactorChallengeController::class, 'store'])->middleware('throttle:two-factor')->name('two-factor.login.store');
    Route::get('/auth/google/complete', [Auth\GoogleController::class, 'complete'])->name('auth.google.complete');
    Route::post('/auth/google/complete', [Auth\GoogleController::class, 'finish'])->middleware('throttle:register')->name('auth.google.finish');
});

Route::get('/auth/google', [Auth\GoogleController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.google.redirect');
Route::get('/auth/google/callback', [Auth\GoogleController::class, 'callback'])->middleware('throttle:20,1')->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [Auth\EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/confirm-password', [Auth\ConfirmPasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [Auth\ConfirmPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.confirm.store');
});

/*
|--------------------------------------------------------------------------
| User dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard')->middleware(['auth'])->group(function () {
    // Reachable while a deletion is pending / before e-mail verification.
    Route::get('/account', [Dashboard\AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [Dashboard\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [Dashboard\AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password');
    Route::get('/account/export', [Dashboard\AccountController::class, 'export'])->middleware(['password.confirm', 'throttle:3,1'])->name('account.export');
    Route::post('/account/delete', [Dashboard\AccountController::class, 'requestDeletion'])->middleware('password.confirm')->name('account.delete');
    Route::post('/account/delete/cancel', [Dashboard\AccountController::class, 'cancelDeletion'])->name('account.delete.cancel');

    Route::middleware('verified.optional')->group(function () {
        Route::get('/', [Dashboard\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/pages', [Dashboard\ProfileController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [Dashboard\ProfileController::class, 'create'])->name('pages.create');
        Route::post('/pages', [Dashboard\ProfileController::class, 'store'])->name('pages.store');
        Route::get('/pages/{profile}/edit', [Dashboard\ProfileController::class, 'edit'])->name('pages.edit');
        Route::get('/pages/{profile}/preview', [Dashboard\ProfileController::class, 'preview'])->name('pages.preview');
        Route::get('/pages/{profile}/qr.{format}', [Dashboard\ProfileController::class, 'qr'])->where('format', 'svg|png')->name('pages.qr');

        Route::middleware('throttle:editor')->group(function () {
            Route::put('/pages/{profile}/details', [Dashboard\ProfileController::class, 'updateDetails'])->name('pages.details');
            Route::put('/pages/{profile}/seo', [Dashboard\ProfileController::class, 'updateSeo'])->name('pages.seo');
            Route::put('/pages/{profile}/theme', [Dashboard\ProfileController::class, 'updateTheme'])->name('pages.theme');
            Route::put('/pages/{profile}/slug', [Dashboard\ProfileController::class, 'updateSlug'])->name('pages.slug');
            Route::post('/pages/{profile}/publish', [Dashboard\ProfileController::class, 'publish'])->name('pages.publish');
            Route::post('/pages/{profile}/unpublish', [Dashboard\ProfileController::class, 'unpublish'])->name('pages.unpublish');
            Route::post('/pages/{profile}/revert', [Dashboard\ProfileController::class, 'revert'])->name('pages.revert');
            Route::post('/pages/{profile}/archive', [Dashboard\ProfileController::class, 'archive'])->name('pages.archive');
            Route::post('/pages/{profile}/restore', [Dashboard\ProfileController::class, 'restore'])->name('pages.restore');
            Route::delete('/pages/{profile}', [Dashboard\ProfileController::class, 'destroy'])->middleware('password.confirm')->name('pages.destroy');

            Route::get('/pages/{profile}/blocks/create', [Dashboard\BlockController::class, 'create'])->name('blocks.create');
            Route::post('/pages/{profile}/blocks', [Dashboard\BlockController::class, 'store'])->name('blocks.store');
            Route::post('/pages/{profile}/blocks/reorder', [Dashboard\BlockController::class, 'reorder'])->name('blocks.reorder');
            Route::get('/blocks/{block}/edit', [Dashboard\BlockController::class, 'edit'])->name('blocks.edit');
            Route::put('/blocks/{block}', [Dashboard\BlockController::class, 'update'])->name('blocks.update');
            Route::post('/blocks/{block}/visibility', [Dashboard\BlockController::class, 'visibility'])->name('blocks.visibility');
            Route::post('/blocks/{block}/duplicate', [Dashboard\BlockController::class, 'duplicate'])->name('blocks.duplicate');
            Route::delete('/blocks/{block}', [Dashboard\BlockController::class, 'destroy'])->name('blocks.destroy');

            Route::post('/pages/{profile}/social', [Dashboard\SocialLinkController::class, 'store'])->name('social.store');
            Route::post('/pages/{profile}/social/reorder', [Dashboard\SocialLinkController::class, 'reorder'])->name('social.reorder');
            Route::put('/social/{link}', [Dashboard\SocialLinkController::class, 'update'])->name('social.update');
            Route::delete('/social/{link}', [Dashboard\SocialLinkController::class, 'destroy'])->name('social.destroy');
        });

        Route::get('/media', [Dashboard\MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [Dashboard\MediaController::class, 'store'])->middleware('throttle:uploads')->name('media.store');
        Route::delete('/media/{media}', [Dashboard\MediaController::class, 'destroy'])->name('media.destroy');

        Route::get('/links', [Dashboard\LinksController::class, 'index'])->name('links.index');
        Route::get('/themes', [Dashboard\ThemesController::class, 'index'])->name('dashboard.themes');
        Route::get('/analytics', [Dashboard\AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [Dashboard\AnalyticsController::class, 'export'])->middleware(['feature:analytics.export', 'throttle:10,1'])->name('analytics.export');

        Route::get('/domains', [Dashboard\DomainController::class, 'index'])->name('domains.index');
        Route::post('/domains', [Dashboard\DomainController::class, 'store'])->middleware('throttle:10,1')->name('domains.store');
        Route::post('/domains/{domain}/verify', [Dashboard\DomainController::class, 'verify'])->middleware('throttle:domain-verify')->name('domains.verify');
        Route::put('/domains/{domain}', [Dashboard\DomainController::class, 'update'])->name('domains.update');
        Route::delete('/domains/{domain}', [Dashboard\DomainController::class, 'destroy'])->name('domains.destroy');

        Route::get('/security', [Dashboard\SecurityController::class, 'index'])->name('security.index');
        Route::post('/security/two-factor', [Dashboard\SecurityController::class, 'startTwoFactor'])->middleware('password.confirm')->name('security.2fa.start');
        Route::post('/security/two-factor/confirm', [Dashboard\SecurityController::class, 'confirmTwoFactor'])->middleware('throttle:6,1')->name('security.2fa.confirm');
        Route::post('/security/two-factor/recovery-codes', [Dashboard\SecurityController::class, 'regenerateRecoveryCodes'])->middleware('password.confirm')->name('security.2fa.recovery');
        Route::delete('/security/two-factor', [Dashboard\SecurityController::class, 'disableTwoFactor'])->middleware('password.confirm')->name('security.2fa.disable');
        Route::post('/security/sessions/logout-others', [Dashboard\SecurityController::class, 'logoutOtherSessions'])->middleware('password.confirm')->name('security.sessions.others');
        Route::delete('/security/sessions/{idHash}', [Dashboard\SecurityController::class, 'destroySession'])->where('idHash', '[a-f0-9]{64}')->name('security.sessions.destroy');
        Route::delete('/security/google', [Dashboard\SecurityController::class, 'unlinkGoogle'])->middleware('password.confirm')->name('security.google.unlink');

        Route::get('/api-tokens', [Dashboard\ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/api-tokens', [Dashboard\ApiTokenController::class, 'store'])->middleware(['feature:api.access', 'password.confirm', 'throttle:10,1'])->name('api-tokens.store');
        Route::delete('/api-tokens/{tokenId}', [Dashboard\ApiTokenController::class, 'destroy'])->whereNumber('tokenId')->name('api-tokens.destroy');

        Route::get('/plan', [Dashboard\PlanController::class, 'show'])->name('plan.show');
        Route::view('/help', 'dashboard.help')->name('dashboard.help');
    });
});

/*
|--------------------------------------------------------------------------
| Superadmin panel (every route is permission-checked server side)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:admin.access', 'admin.2fa'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::get('/profiles', [Admin\ProfileModerationController::class, 'index'])->name('profiles.index');
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reactivate', [Admin\UserController::class, 'reactivate'])->name('users.reactivate');
        Route::post('/users/{user}/cancel-deletion', [Admin\UserController::class, 'cancelDeletion'])->name('users.cancel-deletion');
        Route::post('/users/{user}/verify-email', [Admin\UserController::class, 'markVerified'])->name('users.verify-email');
        Route::post('/users/{user}/resend-verification', [Admin\UserController::class, 'resendVerification'])->name('users.resend-verification');
        Route::post('/users/{user}/package', [Admin\UserController::class, 'assignPackage'])->name('users.package');
        Route::post('/users/{user}/package/{assignment}/revoke', [Admin\UserController::class, 'revokeAssignment'])->name('users.package.revoke');
        Route::post('/users/{user}/overrides', [Admin\UserController::class, 'setOverride'])->name('users.overrides.store');
        Route::delete('/users/{user}/overrides/{featureKey}', [Admin\UserController::class, 'removeOverride'])->name('users.overrides.destroy');
        Route::post('/profiles/{profile}/status', [Admin\ProfileModerationController::class, 'updateStatus'])->name('profiles.status');
        Route::post('/profiles/{profile}/verified', [Admin\ProfileModerationController::class, 'toggleVerified'])->name('profiles.verified');
    });

    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->middleware(['permission:users.delete', 'password.confirm'])->name('users.destroy');
    Route::post('/users/{user}/roles', [Admin\UserController::class, 'syncRoles'])->middleware(['permission:roles.assign', 'password.confirm'])->name('users.roles');

    Route::middleware('permission:packages.manage')->group(function () {
        Route::get('/packages', [Admin\PackageController::class, 'index'])->name('packages.index');
        Route::get('/packages/create', [Admin\PackageController::class, 'create'])->name('packages.create');
        Route::post('/packages', [Admin\PackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{package}/edit', [Admin\PackageController::class, 'edit'])->name('packages.edit');
        Route::put('/packages/{package}', [Admin\PackageController::class, 'update'])->name('packages.update');
        Route::post('/packages/{package}/copy', [Admin\PackageController::class, 'copy'])->name('packages.copy');
        Route::post('/packages/{package}/archive', [Admin\PackageController::class, 'archive'])->name('packages.archive');
        Route::delete('/packages/{package}', [Admin\PackageController::class, 'destroy'])->name('packages.destroy');
        Route::get('/features', [Admin\CatalogController::class, 'features'])->name('features.index');
        Route::put('/features/{feature}', [Admin\CatalogController::class, 'updateFeature'])->name('features.update');
        Route::get('/themes', [Admin\CatalogController::class, 'themes'])->name('themes.index');
        Route::put('/themes/{key}', [Admin\CatalogController::class, 'updateTheme'])->name('themes.update');
    });

    Route::middleware('permission:content.manage')->group(function () {
        Route::get('/pages', [Admin\ContentController::class, 'pages'])->name('pages.index');
        Route::get('/pages/create', [Admin\ContentController::class, 'createPage'])->name('pages.create');
        Route::post('/pages', [Admin\ContentController::class, 'storePage'])->name('pages.store');
        Route::get('/pages/{page}/edit', [Admin\ContentController::class, 'editPage'])->name('pages.edit');
        Route::put('/pages/{page}', [Admin\ContentController::class, 'updatePage'])->name('pages.update');
        Route::delete('/pages/{page}', [Admin\ContentController::class, 'destroyPage'])->name('pages.destroy');
        Route::get('/menus', [Admin\ContentController::class, 'menus'])->name('menus.edit');
        Route::put('/menus', [Admin\ContentController::class, 'updateMenus'])->name('menus.update');
        Route::get('/email-templates', [Admin\ContentController::class, 'templates'])->name('templates.index');
        Route::get('/email-templates/{key}/{locale}', [Admin\ContentController::class, 'editTemplate'])->name('templates.edit');
        Route::put('/email-templates/{key}/{locale}', [Admin\ContentController::class, 'updateTemplate'])->name('templates.update');
        Route::get('/reserved-words', [Admin\CatalogController::class, 'reserved'])->name('reserved.index');
        Route::post('/reserved-words', [Admin\CatalogController::class, 'storeReserved'])->name('reserved.store');
        Route::delete('/reserved-words/{word}', [Admin\CatalogController::class, 'destroyReserved'])->name('reserved.destroy');
        Route::get('/announcement', [Admin\SystemController::class, 'announcement'])->name('announcement.create');
        Route::post('/announcement', [Admin\SystemController::class, 'sendAnnouncement'])->middleware('throttle:20,1')->name('announcement.send');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings/{group}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/{group}', [Admin\SettingsController::class, 'update'])->middleware('password.confirm')->name('settings.update');
        Route::post('/settings/mail/test', [Admin\SettingsController::class, 'testMail'])->middleware('throttle:5,1')->name('settings.mail.test');
        Route::post('/settings/mail/check', [Admin\SettingsController::class, 'checkMail'])->middleware('throttle:5,1')->name('settings.mail.check');
    });

    Route::middleware('permission:media.manage')->group(function () {
        Route::get('/media', [Admin\OperationsController::class, 'media'])->name('media.index');
        Route::delete('/media/{media}', [Admin\OperationsController::class, 'destroyMedia'])->name('media.destroy');
    });

    Route::middleware('permission:domains.manage')->group(function () {
        Route::get('/domains', [Admin\OperationsController::class, 'domains'])->name('domains.index');
        Route::post('/domains/{domain}/disable', [Admin\OperationsController::class, 'disableDomain'])->name('domains.disable');
        Route::post('/domains/{domain}/enable', [Admin\OperationsController::class, 'enableDomain'])->name('domains.enable');
        Route::post('/domains/{domain}/recheck', [Admin\OperationsController::class, 'recheckDomain'])->middleware('throttle:10,1')->name('domains.recheck');
    });

    Route::middleware('permission:messages.view')->group(function () {
        Route::get('/messages', [Admin\OperationsController::class, 'messages'])->name('messages.index');
        Route::get('/messages/{message}', [Admin\OperationsController::class, 'showMessage'])->name('messages.show');
        Route::post('/messages/{message}/archive', [Admin\OperationsController::class, 'archiveMessage'])->name('messages.archive');
        Route::delete('/messages/{message}', [Admin\OperationsController::class, 'destroyMessage'])->name('messages.destroy');
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit-logs', [Admin\OperationsController::class, 'audit'])->name('audit.index');
        Route::get('/security-events', [Admin\OperationsController::class, 'securityEvents'])->name('security-events.index');
    });

    Route::middleware('permission:backups.manage')->group(function () {
        Route::get('/backups', [Admin\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [Admin\BackupController::class, 'store'])->middleware('throttle:5,10')->name('backups.store');
        Route::post('/backups/upload', [Admin\BackupController::class, 'upload'])->middleware('password.confirm')->name('backups.upload');
        Route::post('/backups/{backup}/verify', [Admin\BackupController::class, 'verify'])->name('backups.verify');
        Route::get('/backups/{backup}/download', [Admin\BackupController::class, 'download'])->middleware('password.confirm')->name('backups.download');
        Route::post('/backups/{backup}/restore', [Admin\BackupController::class, 'restore'])->middleware('password.confirm')->name('backups.restore');
        Route::delete('/backups/{backup}', [Admin\BackupController::class, 'destroy'])->middleware('password.confirm')->name('backups.destroy');
    });

    Route::middleware('permission:system.manage')->group(function () {
        Route::get('/system', [Admin\SystemController::class, 'index'])->name('system.index');
        Route::post('/system/probe', [Admin\SystemController::class, 'probe'])->middleware('throttle:5,1')->name('system.probe');
        Route::post('/system/verify-release', [Admin\SystemController::class, 'verifyRelease'])->middleware('throttle:5,1')->name('system.verify-release');
        Route::post('/system/update', [Admin\SystemController::class, 'update'])->middleware('password.confirm')->name('system.update');
        Route::post('/system/maintenance', [Admin\SystemController::class, 'maintenance'])->middleware('throttle:5,1')->name('system.maintenance');
        Route::post('/system/cron-token', [Admin\SystemController::class, 'regenerateCronToken'])->middleware('password.confirm')->name('system.cron-token');
        Route::post('/system/cache', [Admin\SystemController::class, 'clearCache'])->name('system.cache');
    });
});

/*
|--------------------------------------------------------------------------
| Public bio pages (must stay last: /{slug} is a catch-all)
|--------------------------------------------------------------------------
*/
Route::get('/go/{profileId}/{blockId}/{item?}', [ProfilePageController::class, 'click'])
    ->whereNumber(['profileId', 'blockId', 'item'])->middleware('throttle:clicks')->name('profile.click');
Route::get('/{slug}/vcard', [ProfilePageController::class, 'vcard'])->where('slug', '[A-Za-z0-9_-]{3,30}')->name('profile.vcard');
Route::get('/{slug}', [ProfilePageController::class, 'show'])->where('slug', '[A-Za-z0-9_-]{3,30}')->name('profile.show');
