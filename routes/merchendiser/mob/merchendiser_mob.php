 <?php

use App\Http\Controllers\V1\Merchendisher\Mob\PlanogramPostController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyHeaderController;
use App\Http\Controllers\V1\Merchendisher\Mob\CampaignInformationController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyDetailController;
use App\Http\Controllers\V1\Merchendisher\Web\ShelveController;
use App\Http\Controllers\V1\Merchendisher\Web\CompetitorInfoController;
use App\Http\Controllers\V1\Merchendisher\Mob\ComplaintFeedbackController;
use App\Http\Controllers\V1\Merchendisher\Mob\ShelfController;
use App\Http\Controllers\V1\Hariss_Transaction\Mob\HtOrderController;
use App\Http\Controllers\V1\Merchendisher\Web\StockInStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('merchendisher_mob')->group(function () {
        Route::get('Customermerchendiserlist', [ShelveController::class, 'getCustomerDataFile']);
        Route::get('shelve-list', [ShelveController::class, 'getShelvesByMerchandiser']);
        Route::get('planogramlist', [PlanogramPostController::class, 'downloadPlanogramIds']);
        Route::prefix('survey-header')->group(function () {
             Route::post('add', [SurveyHeaderController::class, 'store']);
            Route::get('Survey-list', [SurveyHeaderController::class, 'getSurveyIdsFile']);
            Route::get('list', [SurveyHeaderController::class, 'index']);
            Route::get('list/{id}', [SurveyHeaderController::class, 'getByMerchandiser']);
            Route::get('{id}', [SurveyHeaderController::class, 'show']);
            Route::put('{id}', [SurveyHeaderController::class, 'update']);
            Route::delete('{id}', [SurveyHeaderController::class, 'destroy']);
        });
        Route::prefix('survey-detail')->group(function () {
            Route::post('add', [SurveyDetailController::class, 'store']);
            Route::get('export-excel/{header_id}', [SurveyDetailController::class, 'exportExcel']);
            Route::get('details/{header_id}', [SurveyDetailController::class, 'getList']);
            Route::get('global-search', [SurveyDetailController::class, 'globalSearch']);
        });
        Route::prefix('planogram-post')->group(function () {
            Route::post('create', [PlanogramPostController::class, 'create']);
            Route::get('list', [PlanogramPostController::class, 'index']);
        });
        Route::prefix('compititer')->group(function () {
            Route::post('create', [CompetitorInfoController::class, 'store']);
        });
        Route::prefix('complaint-feedback')->group(function () {
            Route::post('create', [ComplaintFeedbackController::class, 'store']);
        });
        Route::prefix('campaign-info')->group(function () {
            Route::post('create', [CampaignInformationController::class, 'store']);
            Route::get('list', [CampaignInformationController::class, 'index']);
        });
        Route::prefix('shelf')->group(function () {
            Route::post('store_all', [ShelfController::class, 'storeAll']);
            Route::post('list', [ShelfController::class, 'index']);

        });
        Route::prefix('stock-post')->group(function () {
            Route::post('create', [StockInStoreController::class, 'storepost']);
            Route::post('stock-list', [StockInStoreController::class, 'getStockByMerchandiser']);
        });
        Route::prefix('order')->group(function () {
            Route::post('create', [HtOrderController::class, 'store']);
        });
    });

