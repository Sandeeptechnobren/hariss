<?php

use App\Http\Controllers\V1\Assets\Web\IROHeaderController;
use Illuminate\Support\Facades\Route;

Route::prefix('assets_web')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('io_headers')->group(function () {
            Route::get('list', [IROHeaderController::class, 'index']);
            Route::get('generate-osa-code', [IROHeaderController::class, 'generateOsaCode']);
            Route::get('global-search', [IROHeaderController::class, 'global_search']);
            Route::get('{uuid}', [IROHeaderController::class, 'show']);
            Route::post('add', [IROHeaderController::class, 'store']);
            Route::put('{uuid}', [IROHeaderController::class, 'update']);
            Route::delete('{uuid}', [IROHeaderController::class, 'destroy']);
        });
    });
});
