<?php

use App\Http\Controllers\Api\V1\InsightsApiController;
use App\Http\Controllers\Api\V1\LinkApiController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 — prefix /api/v1 (see bootstrap/app.php), Bearer token auth (Sanctum).
| Order: authenticate token -> platform/account/plan checks + usage log -> rate limit.
| Staff/superadmin operations are intentionally not exposed here.
*/
Route::middleware(['auth:sanctum', 'api.access', 'throttle:api'])->name('api.v1.')->group(function () {
    Route::get('/me', [ProfileApiController::class, 'me'])->name('me');

    Route::middleware('abilities:profiles:read')->group(function () {
        Route::get('/profiles', [ProfileApiController::class, 'index'])->name('profiles.index');
        Route::get('/profiles/{profile}', [ProfileApiController::class, 'show'])->whereNumber('profile')->name('profiles.show');
    });

    Route::middleware('abilities:profiles:write')->group(function () {
        Route::post('/profiles', [ProfileApiController::class, 'store'])->name('profiles.store');
        Route::patch('/profiles/{profile}', [ProfileApiController::class, 'update'])->whereNumber('profile')->name('profiles.update');
        Route::post('/profiles/{profile}/publish', [ProfileApiController::class, 'publish'])->whereNumber('profile')->name('profiles.publish');
        Route::post('/profiles/{profile}/unpublish', [ProfileApiController::class, 'unpublish'])->whereNumber('profile')->name('profiles.unpublish');
    });

    Route::get('/profiles/{profile}/links', [LinkApiController::class, 'index'])->whereNumber('profile')->middleware('abilities:links:read')->name('links.index');

    Route::middleware('abilities:links:write')->group(function () {
        Route::post('/profiles/{profile}/links', [LinkApiController::class, 'store'])->whereNumber('profile')->name('links.store');
        Route::patch('/links/{link}', [LinkApiController::class, 'update'])->whereNumber('link')->name('links.update');
        Route::delete('/links/{link}', [LinkApiController::class, 'destroy'])->whereNumber('link')->name('links.destroy');
    });

    Route::get('/profiles/{profile}/analytics', [InsightsApiController::class, 'analytics'])->whereNumber('profile')->middleware('abilities:analytics:read')->name('analytics');

    Route::middleware('abilities:domains:read')->group(function () {
        Route::get('/domains', [InsightsApiController::class, 'domains'])->name('domains.index');
        Route::get('/domains/{domain}', [InsightsApiController::class, 'domain'])->whereNumber('domain')->name('domains.show');
    });
});
