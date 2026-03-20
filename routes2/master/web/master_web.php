<?php

use App\Http\Controllers\V1\Master\Web\CustomerController;
use App\Http\Controllers\V1\Master\Web\ItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('master_web')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('customers')->group(function () {
            Route::get('list', [CustomerController::class, 'index']);
            Route::get('global_search', [CustomerController::class, 'global_search']);
            Route::get('generate-code', [CustomerController::class, 'generateCode']);
            Route::get('{uuid}', [CustomerController::class, 'show']);
            Route::post('add_customer', [CustomerController::class, 'store']);
            Route::put('{uuid}', [CustomerController::class, 'update']);
            Route::delete('{uuid}', [CustomerController::class, 'destroy']);
        });

    });
});
