<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Master\Mob\SalesmanMobController;
use App\Http\Controllers\V1\Master\Mob\SettingController;


Route::prefix('master_mob')->group(function () {
        Route::prefix('salesman')->group(function () {
            Route::post('/login', [SalesmanMobController::class, 'login']);
            
        });
        Route::prefix('salesman')->group(function () {
        Route::post('setting', [SettingController::class, 'store']);
        });
        Route::prefix('salesman')->group(function () {
        Route::get('/warehouses', [SettingController::class, 'show']);
        });
});



