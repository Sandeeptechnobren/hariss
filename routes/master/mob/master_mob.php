<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Master\Mob\SalesmanMobController;
use App\Http\Controllers\V1\Master\Mob\SettingController;
use App\Http\Controllers\V1\Master\Mob\LoadController;
use App\Http\Controllers\V1\Master\Mob\VisitPlanController;
use App\Http\Controllers\V1\Master\Mob\UnloadHeaderController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\NewCustomerController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\OrderController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\AgentDeliveryHeaderController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\InvoiceController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\ReturnController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\CapsCollectionController;
use App\Http\Controllers\V1\Agent_Transaction\Mob\ExchangeController;
use App\Http\Controllers\V1\Assets\Mob\ChillerRequestController;
use App\Http\Controllers\V1\Assets\Mob\ChillerAddController;
use App\Http\Controllers\V1\Assets\Mob\ServiceVisitController;
use App\Http\Controllers\V1\Assets\Mob\CallRegisterController;
use App\Http\Controllers\V1\Assets\Mob\IRController;
use App\Http\Controllers\V1\Assets\Mob\AgreementController;
use App\Http\Controllers\V1\Assets\Mob\AssetTrackingController;

Route::prefix('master_mob')->group(function () {
        Route::prefix('salesman')->group(function () {
            Route::post('/login', [SalesmanMobController::class, 'login']);
            Route::post('setting', [SettingController::class, 'store']);
            Route::get('warehouses', [SettingController::class, 'show']);
            Route::get('/salesman_list', [SettingController::class, 'index']);
            Route::get('attendance-list', [SalesmanMobController::class, 'index']);
            Route::post('attendance', [SalesmanMobController::class, 'store']);
            Route::post('/update/{uuid}', [SalesmanMobController::class, 'update']);
            Route::post('/today-visit', [SalesmanMobController::class, 'listTodayCustomers']);
            Route::post('/requested', [SalesmanMobController::class, 'salesmanrequest']);
            Route::post('/promotion-data', [SalesmanMobController::class, 'getMasterData']);
        });
        Route::prefix('Load')->group(function () {
            Route::post('/create', [LoadController::class, 'store']);
            Route::post('/update/{uuid}', [LoadController::class, 'update']);
            Route::get('/list', [LoadController::class, 'index']);
        });
         Route::prefix('new_customers')->group(function () {
            Route::get('/list', [NewCustomerController::class, 'index']);
            Route::post('/add', [NewCustomerController::class, 'store']);
            Route::get('/{uuid}', [NewCustomerController::class, 'show']);
            Route::put('update/{uuid}', [NewCustomerController::class, 'update']);
            // Route::delete('/{uuid}', [NewCustomerController::class, 'destroy']);
            Route::put('edit/{uuid}', [NewCustomerController::class, 'updatecustomer']);
        });
        Route::prefix('visit_plan')->group(function () {
            Route::get('/list', [VisitPlanController::class, 'index']);
            Route::get('show/{id}', [VisitPlanController::class, 'show']);
            Route::post('/add', [VisitPlanController::class, 'store']);
            Route::put('update/{uuid}', [VisitPlanController::class, 'update']);
            // Route::delete('/{uuid}', [NewCustomerController::class, 'destroy']);
        });
        Route::prefix('unload')->group(function () {
            Route::post('/add', [UnloadHeaderController::class, 'store']);
            Route::get('/list', [UnloadHeaderController::class, 'index']);
        });
        Route::prefix('orders')->group(function () {
            Route::get('/list', [OrderController::class, 'index']);
            Route::get('/{uuid}', [OrderController::class, 'show']);
            Route::post('/add', [OrderController::class, 'store']);
            Route::put('/update/{uuid}', [OrderController::class, 'update']);
        });
        Route::prefix('agent-delivery')->group(function () {
            Route::get('/exportcollapse', [AgentDeliveryHeaderController::class, 'exportdeliverycollapse']);
            Route::get('/list', [AgentDeliveryHeaderController::class, 'index']);
            Route::get('/export', [AgentDeliveryHeaderController::class, 'exportCapsCollection']);
            Route::get('/exportall', [AgentDeliveryHeaderController::class, 'exportCapsCollectionfull']);
            Route::get('/{uuid}', [AgentDeliveryHeaderController::class, 'show']);
            Route::post('/add', [AgentDeliveryHeaderController::class, 'store']);
            Route::put('/update/{uuid}', [AgentDeliveryHeaderController::class, 'update']);
            Route::delete('/{uuid}', [AgentDeliveryHeaderController::class, 'destroy']);
        });
        Route::prefix('invoices')->group(function () {
            // Route::get('exportcollapse', [InvoiceController::class, 'exportInvoiceCollapse']);
            Route::get('/filter', [InvoiceController::class, 'filter']);
            Route::get('list', [InvoiceController::class, 'index']);
            Route::get('show/{uuid}', [InvoiceController::class, 'show']);
            Route::post('create', [InvoiceController::class, 'store']);
            Route::put('update/{uuid}', [InvoiceController::class, 'update']);
            Route::delete('delete/{uuid}', [InvoiceController::class, 'destroy']);
        });
        Route::prefix('returns')->group(function () {
            Route::get('list', [ReturnController::class, 'index']);
            Route::get('show/{uuid}', [ReturnController::class, 'show']);
            Route::post('create', [ReturnController::class, 'store']);
            Route::put('update/{uuid}', [ReturnController::class, 'update']);
            Route::delete('delete/{uuid}', [ReturnController::class, 'destroy']);
        });
        Route::prefix('caps_collection')->group(function () {
            Route::post('create', [CapsCollectionController::class, 'store']);
        });
        Route::prefix('exchange')->group(function () {
            Route::post('create', [ExchangeController::class, 'store']);
            Route::get('/reason-types', [ExchangeController::class, 'index']);
        });
        Route::prefix('chiller-request')->group(function () {
            Route::get('list', [ChillerRequestController::class, 'index']);
            Route::post('add', [ChillerRequestController::class, 'store']);
            Route::get('/model-numbers', [ChillerRequestController::class, 'modelNumbers']);
            Route::get('generate-code', [ChillerRequestController::class, 'generateCode']);
            Route::get('{uuid}', [ChillerRequestController::class, 'show']);
            Route::post('{uuid}', [ChillerRequestController::class, 'update']);
        });
        Route::prefix('add-chiller')->group(function () {
            Route::post('/create', [ChillerAddController::class, 'store']);
        });
});
Route::prefix('technician_mob')->group(function () {        
        Route::prefix('service-visit')->group(function () {
            Route::post('/create', [ServiceVisitController::class, 'store']);
            Route::get('/asset-brand', [ServiceVisitController::class, 'index']);
            Route::get('/spare-details', [ServiceVisitController::class, 'spare']);
        });
        Route::prefix('call-registers')->group(function () {
            Route::get('/pending-bd', [CallRegisterController::class, 'index']);
        });
        Route::prefix('ir-details')->group(function () {
            Route::get('/ir-list', [IRController::class, 'index']);
            Route::post('update-status', [IRController::class, 'updateStatus']);
        }); 
        Route::prefix('agreement')->group(function () {
            Route::post('/create', [AgreementController::class, 'store']);
            Route::get('/pdf/{id}', [AgreementController::class, 'downloadPdf'])->name('agreement.pdf');
        }); 
        Route::prefix('asset-tracking')->group(function () {
            Route::post('/create', [AssetTrackingController::class, 'store']);
            Route::get('/asm-details', [AssetTrackingController::class, 'asmdetails']);
        }); 
});