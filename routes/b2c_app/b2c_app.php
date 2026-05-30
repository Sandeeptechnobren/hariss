<?php

use App\Http\Controllers\V1\B2C_App\UserOTPController;
use App\Http\Controllers\V1\Agent_Transaction\InvoiceController;
use App\Http\Controllers\V1\B2C_App\Agent_Transaction\B2COrderController;
use App\Http\Controllers\V1\B2C_App\Master\B2CUserController;
use App\Http\Controllers\V1\B2C_App\Master\DataStoragePathController;
use App\Http\Controllers\V1\B2C_App\Master\PromotionController;
use App\Http\Controllers\V1\B2C_App\Loyality_Management\LoyalityPointController;

// 🔓 PUBLIC (NO AUTH)
Route::prefix('b2c_app')->group(function () {
    Route::post('/send-otp', [UserOTPController::class, 'sendOtp']);
    Route::post('/verify-otp', [UserOTPController::class, 'verifyOtp']);
    Route::middleware('auth:b2c_app')->group(function () {
        Route::prefix('users')->group(function () {
            Route::put('update/{uuid}', [B2CUserController::class, 'update']);
        });
        Route::prefix('invoices')->group(function () {
            Route::get('agent-customer/{uuid}', [InvoiceController::class, 'getInvoicesByCustomerUuid']);
            Route::get('exportall', [InvoiceController::class, 'exportInvoiceFullExport']);
        });
        Route::prefix('orders')->group(function () {
            Route::post('/add', [B2COrderController::class, 'store']);
            // Route::post('/globalFilter', [OrderController::class, 'globalFilter']);
            Route::post('export-pdf/{uuid}', [B2COrderController::class, 'exportOrders']);
            Route::get('/list', [B2COrderController::class, 'index']);
            Route::get('/{uuid}', [B2COrderController::class, 'show']);
            Route::put('/update/{uuid}', [B2COrderController::class, 'update']);
        });
        Route::prefix('item')->group(function () {
            Route::post('list', [DataStoragePathController::class, 'store']);
        });
        Route::prefix('loyality_management')->group(function () {
            Route::get('list', [LoyalityPointController::class, 'index']);
        });
        Route::prefix('promotion')->group(function () {
            Route::get('list', [PromotionController::class, 'index']);
        });
    });
});
