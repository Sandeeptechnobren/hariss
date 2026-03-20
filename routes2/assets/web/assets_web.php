<?php

use App\Http\Controllers\V1\Assets\Web\InstallationOrderHeaderController;
use Illuminate\Support\Facades\Route;

Route::prefix('assets_web')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('io_headers')->group(function () {
            Route::get('list', [InstallationOrderHeaderController::class, 'index']);
            Route::get('generate-osa-code', [InstallationOrderHeaderController::class, 'generateOsaCode']);
            Route::get('global-search', [InstallationOrderHeaderController::class, 'global_search']);
            Route::get('{uuid}', [InstallationOrderHeaderController::class, 'show']);
            Route::post('add', [InstallationOrderHeaderController::class, 'store']);
            Route::put('{uuid}', [InstallationOrderHeaderController::class, 'update']);
            Route::delete('{uuid}', [InstallationOrderHeaderController::class, 'destroy']);
        });
    });
});
