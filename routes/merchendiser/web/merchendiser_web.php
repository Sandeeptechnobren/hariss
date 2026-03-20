<?php
use App\Http\Controllers\V1\Merchendisher\Mob\PlanogramPostController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyHeaderController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyDetailController;
use App\Http\Controllers\V1\Merchendisher\Web\AsignInventoryController;
use App\Http\Controllers\V1\Merchendisher\Web\StockInStoreController;
use App\Http\Controllers\V1\Merchendisher\Web\ShelveController;
use App\Http\Controllers\V1\Merchendisher\Web\CompetitorInfoController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyQuestionController;
use App\Http\Controllers\V1\Merchendisher\Web\ComplaintFeedbackController;
use App\Http\Controllers\V1\Merchendisher\Web\AssetTrackingController;
use Illuminate\Support\Facades\Route;


Route::prefix('merchendisher_web')->group(function () {
    Route::middleware('auth:api')->group(function () {
         Route::prefix('survey-header')->group(function () {
            Route::get('list', [SurveyHeaderController::class, 'index']);
            Route::get('{id}', [SurveyHeaderController::class, 'show']);
            Route::post('add', [SurveyHeaderController::class, 'store']);
            Route::put('{id}', [SurveyHeaderController::class, 'update']);
            Route::delete('{id}', [SurveyHeaderController::class, 'destroy']);
        });
            Route::prefix('survey-detail')->group(function () {
                Route::post('add', [SurveyDetailController::class, 'store']);
                Route::get('details/{header_id}', [SurveyDetailController::class, 'getList']);
                Route::get('global-search', [SurveyDetailController::class, 'globalSearch']);
            });
        Route::prefix('planogram-post')->group(function () {
            Route::post('create', [PlanogramPostController::class, 'create']);
            Route::get('list', [PlanogramPostController::class, 'index']);
        });
        Route::prefix('asign-inventory')->group(function () {
            Route::get('export-stocks', [AsignInventoryController::class, 'export']);
            Route::post('asign', [AsignInventoryController::class, 'store']);
            Route::get('list', [AsignInventoryController::class, 'index']);
            Route::get('/{uuid}', [AsignInventoryController::class, 'show']);
            Route::put('/{uuid}', [AsignInventoryController::class, 'update']);
            Route::delete('/{uuid}', [AsignInventoryController::class, 'destroy']);
            Route::post('bulk-upload', [AsignInventoryController::class, 'bulkUpload']);
        });
           Route::prefix('stockinstore')->group(function () {
            Route::post('create', [StockInStoreController::class, 'store']);
            Route::get('dropdownlistcustomers', [StockInStoreController::class, 'getDropdownList']);
            Route::get('list', [StockInStoreController::class, 'index']);
            Route::get('show/{uuid}', [StockInStoreController::class, 'show']);
            Route::put('update/{uuid}', [StockInStoreController::class, 'update']);
            Route::delete('delete/{uuid}', [StockInStoreController::class, 'destroy']);
            Route::post('bluckupload', [StockInStoreController::class, 'bulkUpload']);
        });
         Route::prefix('shelve')->group(function () {
            Route::post('bluckupload', [ShelveController::class, 'import']);
            Route::get('export', [ShelveController::class, 'exportShelves']);
            });
         Route::prefix('compititer')->group(function () {
            Route::get('list', [CompetitorInfoController::class, 'index']);
            Route::get('show/{uuid}', [CompetitorInfoController::class, 'show']);
            });

         Route::prefix('survey')->group(function () {
            Route::post('importsurvey', [SurveyController::class, 'import']);
            });
        Route::prefix('survey-questions')->group(function () {
            Route::get('get/{survey_id}', [SurveyQuestionController::class, 'getBySurveyId']);
            Route::post('bluckupload', [SurveyQuestionController::class, 'import']);
            Route::get('exportfile', [SurveyQuestionController::class, 'export']);
       });
        //     Route::prefix('complaint-feedback')->group(function () {
        //         Route::get('list', [ComplaintFeedbackController::class, 'index']);
        //         Route::get('show/{uuid}', [ComplaintFeedbackController::class, 'show']);
        //         Route::post('create', [ComplaintFeedbackController::class, 'store']);
        // });
             Route::prefix('asset-tracking')->group(function () {
                Route::get('list', [AssetTrackingController::class, 'index']);
                Route::get('show/{uuid}', [AssetTrackingController::class, 'show']);
        });
     });
});
