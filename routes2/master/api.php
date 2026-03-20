<?php

use App\Http\Controllers\V1\Master\Web\AuthController;
use App\Http\Controllers\V1\Master\Web\AreaController;
use App\Http\Controllers\V1\Master\Web\CompanyController;
use App\Http\Controllers\V1\Master\Web\VehicleController;
use App\Http\Controllers\V1\Master\Web\CountryController;
use App\Http\Controllers\V1\Master\Web\RegionController;
use App\Http\Controllers\V1\Master\Web\WarehouseController;
use App\Http\Controllers\V1\Master\Web\CompanyCustomerController;
use App\Http\Controllers\V1\Master\Web\ItemController;
use App\Http\Controllers\V1\Master\Web\PricingHeaderController;
use App\Http\Controllers\V1\Master\Web\PromotionHeaderController;
use App\Http\Controllers\V1\Master\Web\PromotionDetailController;
use App\Http\Controllers\V1\Master\Web\RouteController;
use App\Http\Controllers\V1\Master\Web\DiscountController;
use App\Http\Controllers\V1\Settings\Web\CustomerCategoryController;
use App\Http\Controllers\V1\Settings\Web\CustomerSubCategoryController;
use App\Http\Controllers\V1\Settings\Web\CustomerTypeController;
use App\Http\Controllers\V1\Settings\Web\UsertypesController;
use App\Http\Controllers\V1\Settings\Web\ItemCategoryController;
use App\Http\Controllers\V1\Settings\Web\ItemSubCategoryController;
use App\Http\Controllers\V1\Settings\Web\SalesmanTypeController;
use App\Http\Controllers\V1\Settings\Web\ExpenseTypeController;
use App\Http\Controllers\V1\Settings\Web\DiscountTypeController;
use App\Http\Controllers\V1\Settings\Web\OutletChannelController;
use App\Http\Controllers\V1\Settings\Web\PromotionTypeController;
use App\Http\Controllers\V1\Settings\Web\RouteTypeController;
use App\Http\Controllers\V1\Settings\Web\RoleController;
use App\Http\Controllers\V1\Settings\Web\SubMenuController;
use App\Http\Controllers\V1\Assets\Web\ChillerController;
use App\Http\Controllers\V1\Assets\Web\ChillerRequestController;
use App\Http\Controllers\V1\Assets\Web\VendorController;
use App\Http\Controllers\V1\Merchendisher\Web\ShelveController;
use App\Http\Controllers\V1\Master\Web\AgentCustomerController;
use App\Http\Controllers\V1\Master\Web\PricingDetailController;
use App\Http\Controllers\V1\Master\Web\SalesmanController;
use App\Http\Controllers\V1\Merchendisher\Web\PlanogramController;
use App\Http\Controllers\V1\Merchendisher\Web\PlanogramImageController;
use App\Http\Controllers\V1\Merchendisher\Mob\PlanogramPostController;
use App\Http\Controllers\V1\Settings\Web\CompanyTypeController;
use App\Http\Controllers\V1\Settings\Web\MenuController;
use App\Http\Controllers\V1\Settings\Web\ServiceTypeController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyQuestionController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyHeaderController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyDetailController;
use App\Http\Controllers\V1\Merchendisher\Web\ComplaintFeedbackController;
use App\Http\Controllers\V1\Merchendisher\Mob\CampaignInformationController;
use App\Http\Controllers\V1\Merchendisher\Web\CompetitorInfoController;
use App\Http\Controllers\V1\Settings\Web\UomController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\CodeController;
use App\Http\Controllers\V1\Settings\Web\PermissionController;
use App\Http\Controllers\V1\Settings\Web\WarehouseStockController;

Route::prefix('master')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/tokenCheck', [AuthController::class, 'tokenCheck']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logoutall', [AuthController::class, 'logoutall']);
        Route::prefix('company')->group(function () {
            Route::get('list_company', [CompanyController::class, 'index']);
            Route::get('global_search', [CompanyController::class, 'global_search']);
            Route::post('add_company', [CompanyController::class, 'store']);
            Route::get('company/{id}', [CompanyController::class, 'show']);
            Route::put('company/{id}', [CompanyController::class, 'update']);
            Route::delete('company/{id}', [CompanyController::class, 'destroy']);
        });
        Route::prefix('country')->group(function () {
            Route::get('list_country/', [CountryController::class, 'index']);
            Route::get('global_search', [CountryController::class, 'global_search']);

            Route::get('country/{id}', [CountryController::class, 'show']);
            Route::post('add_country/', [CountryController::class, 'store']);
            Route::put('update_country/{id}', [CountryController::class, 'update']);
            Route::delete('/{id}', [CountryController::class, 'destroy']);
        });
        Route::prefix('region')->group(function () {
            Route::get('list_region/', [RegionController::class, 'index']);
            Route::get('region_dropdown/', [RegionController::class, 'regionDropdown']);
            Route::get('global_search', [RegionController::class, 'global_search']);
            Route::get('{id}', [RegionController::class, 'show']);
            Route::post('add_region/', [RegionController::class, 'store']);
            Route::put('update_region/{id}', [RegionController::class, 'update']);
            Route::delete('/{id}', [RegionController::class, 'destroy']);
        });
        Route::prefix('area')->group(function () {
            Route::get('list_area', [AreaController::class, 'index']);
            Route::post('add_area', [AreaController::class, 'store']);
            Route::get('area/{id}', [AreaController::class, 'show']);
            Route::put('area/{id}', [AreaController::class, 'update']);
            Route::delete('area/{id}', [AreaController::class, 'destroy']);
            Route::get('areadropdown', [AreaController::class, 'areaDropdown']);
            Route::get('global_search', [AreaController::class, 'global_search']);
        });
        Route::prefix('warehouse')->middleware('auth:api')->group(function () {
            Route::get('/list', [WarehouseController::class, 'index']);
            Route::get('/global_search', [WarehouseController::class, 'global_search']);
            Route::post('/create', [WarehouseController::class, 'store']);
            // Route::get('/{id}', [WarehouseController::class, 'show']);
            Route::get('{id}', [WarehouseController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/{id}', [WarehouseController::class, 'update']);
            Route::delete('/{id}', [WarehouseController::class, 'destroy']);
            Route::get('/list_warehouse/active', [WarehouseController::class, 'active']);
            Route::get('/type/{type}', [WarehouseController::class, 'byType']);
            Route::put('/{id}/status', [WarehouseController::class, 'updateStatus']);
            Route::get('/region/{regionId}', [WarehouseController::class, 'byRegion']);
            Route::get('/area/{areaId}', [WarehouseController::class, 'byArea']);
            Route::post('/export', [WarehouseController::class, 'exportWarehouses']);
            Route::post('/multiple_status_update', [WarehouseController::class, 'updateMultipleStatus']);


        });

        Route::prefix('companycustomer')->group(function () {
            Route::get('list', [CompanyCustomerController::class, 'index']);
            Route::post('/export', [CompanyCustomerController::class, 'export']);
            Route::post('/export', [CompanyCustomerController::class, 'export']);
            Route::get('{id}', [CompanyCustomerController::class, 'show']);
            Route::post('create', [CompanyCustomerController::class, 'store']);
            Route::put('{id}/update', [CompanyCustomerController::class, 'update']);
            Route::delete('{id}/delete', [CompanyCustomerController::class, 'destroy']);
            Route::get('region/{regionId}', [CompanyCustomerController::class, 'getByRegion']);
            Route::get('area/{areaId}', [CompanyCustomerController::class, 'getByArea']);
            Route::get('active', [CompanyCustomerController::class, 'getActive']);
            Route::post('bulk-update-status', [CompanyCustomerController::class, 'bulkUpdateStatus']);

        });
        Route::prefix('vehicle')->group(function () {
            Route::get('/list', [VehicleController::class, 'index']);
            Route::get('/list', [VehicleController::class, 'index']);
            Route::get('{id}', [VehicleController::class, 'show'])->where('id', '[0-9]+');
            Route::post('create', [VehicleController::class, 'store']);
            Route::put('{id}/update', [VehicleController::class, 'update']);
            Route::delete('{id}/delete', [VehicleController::class, 'destroy']);
            Route::get('warehouse/{warehouseId}', [VehicleController::class, 'getByWarehouse']);
            Route::get('active', [VehicleController::class, 'getActive']);
            Route::get('global_search', [VehicleController::class, 'global_search']);
            Route::post('export', [VehicleController::class, 'exportVehicles']);
            Route::post('/multiple_status_update', [VehicleController::class, 'updateMultipleStatus']); 


        });
        Route::prefix('route')->group(function () {
            Route::get('/list_routes', [RouteController::class, 'index']);
            Route::post('/export', [RouteController::class, 'export']);
            Route::post('/export', [RouteController::class, 'export']);
            Route::post('/add_routes', [RouteController::class, 'store']);
            Route::get('/routes/{route}', [RouteController::class, 'show']);
            Route::put('/routes/{route}', [RouteController::class, 'update']);
            Route::delete('/routes/{route}', [RouteController::class, 'destroy']);
            Route::get('global_search', [RouteController::class, 'global_search']);
            Route::post('bulk-update-status', [RouteController::class, 'bulkUpdateStatus']);

        });
        Route::prefix('agent_customers')->group(function () {
            Route::get('list/', [AgentCustomerController::class, 'index']);
            Route::post('/export', [AgentCustomerController::class, 'export']);
            Route::post('/export', [AgentCustomerController::class, 'export']);
            Route::get('generate-code', [AgentCustomerController::class, 'generateCode']);
            Route::get('{uuid}', [AgentCustomerController::class, 'show']);
            Route::post('add/', [AgentCustomerController::class, 'store']);
            Route::put('update/{uuid}', [AgentCustomerController::class, 'update']);
            Route::delete('{uuid}', [AgentCustomerController::class, 'destroy']);
            Route::post('bulk-update-status', [AgentCustomerController::class, 'bulkUpdateStatus']);

        });
        Route::prefix('items')->group(function () {
            Route::get('list/', [ItemController::class, 'index']);
            Route::get('{id}', [ItemController::class, 'show'])->where('id', '[0-9]+');
            Route::post('add/', [ItemController::class, 'store']);
            Route::put('update/{id}', [ItemController::class, 'update']);
            Route::delete('{id}', [ItemController::class, 'destroy']);
            Route::get('global-search', [ItemController::class, 'globalSearch']);
            Route::post('update-status', [ItemController::class, 'updateMultipleItemStatus']);
        });
        Route::prefix('salesmen')->group(function () {
            Route::get('exportfile', [SalesmanController::class,'exportSalesmen']);
            Route::get('list',[SalesmanController::class, 'index']);
            Route::get('exportfile', [SalesmanController::class,'exportSalesmen']);
            Route::post('bulk-upload', [SalesmanController::class, 'bulkUpload']);
            Route::get('generate-code', [SalesmanController::class, 'generateCode']);
            Route::get('{uuid}', [SalesmanController::class, 'show']);
            Route::post('update-status', [SalesmanController::class, 'updateMultipleSalesmanStatus']);
            
            Route::post('add/', [SalesmanController::class, 'store']);
            Route::put('update/{uuid}', [SalesmanController::class, 'update']);
            Route::delete('{uuid}', [SalesmanController::class, 'destroy']);
            Route::post('update-status', [SalesmanController::class, 'updateMultipleSalesmanStatus']);
            Route::post('update-status', [SalesmanController::class, 'updateMultipleSalesmanStatus']);
        });
        Route::prefix('pricing-headers')->group(function () {
            Route::get('list/', [PricingHeaderController::class, 'index']);
            Route::get('generate-code', [PricingHeaderController::class, 'generateCode']);
            Route::get('{uuid}', [PricingHeaderController::class, 'show']);
            Route::post('add/', [PricingHeaderController::class, 'store']);
            Route::put('update/{uuid}', [PricingHeaderController::class, 'update']);
            Route::delete('{uuid}', [PricingHeaderController::class, 'destroy']);
        });
        Route::prefix('pricing-details')->group(function () {
            Route::get('list', [PricingDetailController::class, 'index']);
            Route::get('global_search', [PricingDetailController::class, 'global_search']);
            Route::get('generate-code', [PricingDetailController::class, 'generateCode']);
            Route::get('{uuid}', [PricingDetailController::class, 'show']);
            Route::post('add', [PricingDetailController::class, 'store']);
            Route::put('update/{uuid}', [PricingDetailController::class, 'update']);
            Route::delete('{uuid}', [PricingDetailController::class, 'destroy']);
        });
        Route::prefix('promotion-headers')->group(function () {
            Route::get('list', [PromotionHeaderController::class, 'index']);
            Route::post('create', [PromotionHeaderController::class, 'store']);
            Route::get('show/{id}', [PromotionHeaderController::class, 'show']);
            Route::put('update/{id}', [PromotionHeaderController::class, 'update']);
            Route::delete('delete/{id}', [PromotionHeaderController::class, 'destroy']);
        });
        Route::prefix('promotion-details')->group(function () {
            Route::get('list', [PromotionDetailController::class, 'index']);
            Route::post('create', [PromotionDetailController::class, 'store']);
            Route::get('show/{uuid}', [PromotionDetailController::class, 'show']);
            Route::put('update/{uuid}', [PromotionDetailController::class, 'update']);
            Route::delete('delete/{uuid}', [PromotionDetailController::class, 'destroy']);
        });
        Route::prefix('discount')->group(function () {
            Route::get('list', [DiscountController::class, 'index']);
            Route::get('discount/{uuid}', [DiscountController::class, 'show']);
            Route::post('create', [DiscountController::class, 'store']);
            Route::put('update/{uuid}', [DiscountController::class, 'update']);
            Route::delete('delete/{uuid}', [DiscountController::class, 'destroy']);
            Route::get('global_search', [DiscountController::class, 'global_search']);
        });
    });
});

Route::prefix('settings')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('roles')->group(function () {
            Route::get('list', [RoleController::class, 'index']);           // GET all roles
            Route::get('{id}', [RoleController::class, 'show']);         // GET single role
            Route::post('add', [RoleController::class, 'store']);          // POST create role
            Route::put('{id}', [RoleController::class, 'update']);       // PUT update role
            Route::delete('{id}', [RoleController::class, 'destroy']);   // DELETE role
        });
        // Route::prefix('permissions')->group(function () {
        //     Route::get('list', [PermissionController::class, 'index']);         // GET all permissions
        //     Route::get('{id}', [PermissionController::class, 'show']);  // GET single permission
        //     Route::post('add', [PermissionController::class, 'store']);         // POST create permission
        //     Route::put('{id}', [PermissionController::class, 'update']); // PUT update permission
        //     Route::delete('{id}', [PermissionController::class, 'destroy']); // DELETE permission
        // });
        Route::prefix('submenu')->group(function () {
            Route::get('list', [SubMenuController::class, 'index']);
            Route::get('global_search', [SubMenuController::class, 'global_search']);
            Route::get('generate-code', [SubMenuController::class, 'generateCode']);
            Route::get('{uuid}', [SubMenuController::class, 'show']);
            Route::post('add', [SubMenuController::class, 'store']);
            Route::put('{uuid}', [SubMenuController::class, 'update']);
            Route::delete('{uuid}', [SubMenuController::class, 'destroy']);
        });
        Route::prefix('item_category')->group(function () {
            Route::get('list', [ItemCategoryController::class, 'index']);
            Route::get('{id}', [ItemCategoryController::class, 'show']);
            Route::post('create', [ItemCategoryController::class, 'store']);
            Route::put('{id}', [ItemCategoryController::class, 'update']);
            Route::delete('{id}', [ItemCategoryController::class, 'destroy']);
        });
        Route::prefix('outlet-channels')->group(function () {
            Route::get('list/', [OutletChannelController::class, 'index']);
            Route::get('/{id}', [OutletChannelController::class, 'show']);
            Route::post('/', [OutletChannelController::class, 'store']);
            Route::put('/{id}', [OutletChannelController::class, 'update']);
            Route::delete('/{id}', [OutletChannelController::class, 'destroy']);
        });
        Route::prefix('item-sub-category')->group(function () {
            Route::get('list', [ItemSubCategoryController::class, 'index']);
            Route::get('{id}', [ItemSubCategoryController::class, 'show']);
            Route::post('create', [ItemSubCategoryController::class, 'store']);
            Route::put('{id}/update', [ItemSubCategoryController::class, 'update']);
            Route::delete('{id}/delete', [ItemSubCategoryController::class, 'destroy']);
        });
        Route::prefix('customer-category')->group(function () {
            Route::get('list', [CustomerCategoryController::class, 'index']);
            Route::get('global_search', [CustomerCategoryController::class, 'global_search']);
            Route::get('{id}', [CustomerCategoryController::class, 'show']);
            Route::post('create', [CustomerCategoryController::class, 'store']);
            Route::put('{id}/update', [CustomerCategoryController::class, 'update']);
            Route::delete('{id}/delete', [CustomerCategoryController::class, 'destroy']);
        });
        Route::prefix('customer-sub-category')->group(function () {
            Route::get('list', [CustomerSubCategoryController::class, 'index']);
            Route::get('{id}', [CustomerSubCategoryController::class, 'show']);
            Route::post('create', [CustomerSubCategoryController::class, 'store']);
            Route::put('{id}/update', [CustomerSubCategoryController::class, 'update']);
            Route::delete('{id}/delete', [CustomerSubCategoryController::class, 'destroy']);
        });

        Route::prefix('user-type')->group(function () {
            Route::get('list', [UsertypesController::class, 'index']);
            Route::get('{id}', [UsertypesController::class, 'show']);
            Route::post('create', [UsertypesController::class, 'store']);
            Route::put('{id}', [UsertypesController::class, 'update']);
            Route::delete('{id}', [UsertypesController::class, 'destroy']);
        });
        Route::prefix('customer-type')->group(function () {
            Route::get('list', [CustomerTypeController::class, 'index']);
            Route::get('{id}', [CustomerTypeController::class, 'show']);
            Route::post('create', [CustomerTypeController::class, 'store']);
            Route::put('{id}', [CustomerTypeController::class, 'update']);
            Route::delete('{id}', [CustomerTypeController::class, 'destroy']);
        });
        Route::prefix('route-type')->group(function () {
            Route::get('list', [RouteTypeController::class, 'index']);
            Route::get('{id}', [RouteTypeController::class, 'show']);
            Route::post('add', [RouteTypeController::class, 'store']);
            Route::put('{id}/update', [RouteTypeController::class, 'update']);
            Route::delete('{id}/delete', [RouteTypeController::class, 'destroy']);
        });
        Route::prefix('promotion_type')->group(function () {
            Route::get('list', [PromotionTypeController::class, 'index']);
            Route::get('{id}', [PromotionTypeController::class, 'show']);
            Route::post('create', [PromotionTypeController::class, 'store']);
            Route::put('{id}/update', [PromotionTypeController::class, 'update']);
            Route::delete('{id}/delete', [PromotionTypeController::class, 'destroy']);
        });
        Route::prefix('discount_type')->group(function () {
            Route::get('list', [DiscountTypeController::class, 'index']);
            Route::get('{id}', [DiscountTypeController::class, 'show']);
            Route::post('create', [DiscountTypeController::class, 'store']);
            Route::put('{id}/update', [DiscountTypeController::class, 'update']);
            Route::delete('{id}/delete', [DiscountTypeController::class, 'destroy']);
        });
        Route::prefix('expense_type')->group(function () {
            Route::get('list', [ExpenseTypeController::class, 'index']);
            Route::get('{id}', [ExpenseTypeController::class, 'show']);
            Route::post('create', [ExpenseTypeController::class, 'store']);
            Route::put('{id}/update', [ExpenseTypeController::class, 'update']);
            Route::delete('{id}/delete', [ExpenseTypeController::class, 'destroy']);
        });
        Route::prefix('salesman_type')->group(function () {
            Route::get('list', [SalesmanTypeController::class, 'index']);
            Route::get('{id}', [SalesmanTypeController::class, 'show']);
            Route::post('create', [SalesmanTypeController::class, 'store']);
            Route::put('{id}/update', [SalesmanTypeController::class, 'update']);
            Route::delete('{id}/delete', [SalesmanTypeController::class, 'destroy']);
        });
        Route::prefix('company-types')->group(function () {
            Route::get('list', [CompanyTypeController::class, 'index']);
            Route::get('show/{id}', [CompanyTypeController::class, 'show']);
            Route::get('generate-code', [CompanyTypeController::class, 'generateCode']);
            Route::post('add', [CompanyTypeController::class, 'store']);
            Route::put('update/{id}', [CompanyTypeController::class, 'update']);
            Route::delete('delete/{id}', [CompanyTypeController::class, 'destroy']);
        });
        Route::prefix('menus')->group(function () {
            Route::get('list', [MenuController::class, 'index']);
            Route::get('global-search', [MenuController::class, 'globalSearch']);
            Route::get('generate-code', [MenuController::class, 'generateCode']);
            Route::get('{uuid}', [MenuController::class, 'show']);
            Route::post('add', [MenuController::class, 'store']);
            Route::put('update/{uuid}', [MenuController::class, 'update']);
            Route::delete('{uuid}', [MenuController::class, 'destroy']);
        });
        Route::prefix('service-types')->group(function () {
            Route::get('list', [ServiceTypeController::class, 'index']);
            Route::get('show/{uuid}', [ServiceTypeController::class, 'show']);
            Route::get('generate-code', [ServiceTypeController::class, 'generateCode']);
            Route::post('add', [ServiceTypeController::class, 'store']);
            Route::put('update/{uuid}', [ServiceTypeController::class, 'update']);
            Route::delete('delete/{uuid}', [ServiceTypeController::class, 'destroy']);
            Route::get('export', [ServiceTypeController::class, 'exportCsv']);
        });
        Route::prefix('roles')->group(function () {
            Route::get('list', [RoleController::class, 'index']);
            Route::post('/assign-permissions/{id}', [RoleController::class, 'assignPermissionsWithMenu']);
            Route::put('/permissions/{id}', [RoleController::class, 'updateRolePermissions']);          // GET all roles
            Route::get('{id}', [RoleController::class, 'show']);         // GET single role
            Route::post('add', [RoleController::class, 'store']);          // POST create role
            Route::put('{id}', [RoleController::class, 'update']);       // PUT update role
            Route::delete('{id}', [RoleController::class, 'destroy']);   // DELETE role
        });
        Route::prefix('permissions')->group(function () {
            Route::get('list', [PermissionController::class, 'index']);         // GET all permissions
            Route::get('{id}', [PermissionController::class, 'show']);  // GET single permission
            Route::post('add', [PermissionController::class, 'store']);         // POST create permission
            Route::put('{id}', [PermissionController::class, 'update']); // PUT update permission
            Route::delete('{id}', [PermissionController::class, 'destroy']); // DELETE permission
        });
        Route::prefix('uom')->group(function () {
            Route::get('list', [UomController::class, 'index']);
            Route::post('add', [UomController::class, 'store']);
            Route::get('{uuid}', [UomController::class, 'show']);
            Route::put('{uuid}', [UomController::class, 'update']);
            Route::delete('{uuid}', [UomController::class, 'destroy']);
        });

        Route::prefix('warehouse-stocks')->group(function () {
            Route::get('list', [WarehouseStockController::class, 'index']);           // List all stocks
            Route::post('add', [WarehouseStockController::class, 'store']);          // Create new stock
            Route::get('{uuid}', [WarehouseStockController::class, 'show']);      // Get stock by UUID
            Route::put('{uuid}', [WarehouseStockController::class, 'update']);    // Update stock by UUID
            Route::delete('{uuid}', [WarehouseStockController::class, 'destroy']); // Soft delete stock
        });
    });
});

Route::prefix('assets')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('chiller')->group(function () {
            Route::get('list_chillers', [ChillerController::class, 'index']);
            Route::get('generate-code', [ChillerController::class, 'generateCode']);
            Route::get('{uuid}', [ChillerController::class, 'show']);
            Route::post('add_chiller', [ChillerController::class, 'store']);
            Route::put('{uuid}', [ChillerController::class, 'update']);
            Route::delete('{uuid}', [ChillerController::class, 'destroy']);
        });
        Route::prefix('vendor')->group(function () {
            Route::get('list_vendors', [VendorController::class, 'index']);
            Route::get('generate-code', [VendorController::class, 'generateCode']);
            Route::get('vendor/{uuid}', [VendorController::class, 'show']);
            Route::post('add_vendor', [VendorController::class, 'store']);
            Route::put('update_vendor/{uuid}', [VendorController::class, 'update']);
            Route::delete('delete_vendor/{uuid}', [VendorController::class, 'destroy']);
        });
        Route::prefix('chiller-request')->group(function () {
            Route::get('list', [ChillerRequestController::class, 'index']);
            Route::get('global_search', [ChillerRequestController::class, 'global_search']);
            Route::get('generate-code', [ChillerRequestController::class, 'generateCode']);
            Route::get('{uuid}', [ChillerRequestController::class, 'show']);
            Route::post('add', [ChillerRequestController::class, 'store']);
            Route::post('{uuid}', [ChillerRequestController::class, 'update']);
            Route::delete('{uuid}', [ChillerRequestController::class, 'destroy']);
        });
    });
});
Route::prefix('merchendisher')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('shelves')->group(function () {
            Route::get('list', [ShelveController::class, 'index']);
            Route::post('add', [ShelveController::class, 'store']);
            Route::get('global-search', [ShelveController::class, 'globalSearch']);
            Route::get('dropdown', [ShelveController::class, 'dropdown']);
            Route::get('show/{uuid}', [ShelveController::class, 'show']);
            Route::put('update/{uuid}', [ShelveController::class, 'update']);
            Route::delete('destroy/{uuid}', [ShelveController::class, 'destroy']);
            Route::get('export', [ShelveController::class, 'exportShelves']);
        });
        Route::prefix('survey')->group(function () {
            Route::get('list', [SurveyController::class, 'index']);
            Route::post('add', [SurveyController::class, 'store']);
            Route::get('global-search', [SurveyController::class, 'globalSearch']);
            Route::get('/survey-export', [SurveyController::class, 'export']);
            Route::get('{id}', [SurveyController::class, 'show']);
            Route::put('{id}', [SurveyController::class, 'update']);
            Route::delete('{id}', [SurveyController::class, 'destroy']);
        });

        Route::prefix('survey-questions')->group(function () {
            Route::get('list', [SurveyQuestionController::class, 'index']);
            Route::post('add', [SurveyQuestionController::class, 'store']);
            Route::get('global-search', [SurveyQuestionController::class, 'globalSearch']);
            Route::get('{id}', [SurveyQuestionController::class, 'show']);
            Route::put('{id}', [SurveyQuestionController::class, 'update']);
            Route::delete('{id}', [SurveyQuestionController::class, 'destroy']);
            Route::get('get/{survey_id}', [SurveyQuestionController::class, 'getBySurveyId']);
        });


        Route::prefix('planogram')->group(function () {
            Route::get('list', [PlanogramController::class, 'index']);
            Route::get('show/{uuid}', [PlanogramController::class, 'show']);
            Route::post('create', [PlanogramController::class, 'store']);
            Route::put('update/{uuid}', [PlanogramController::class, 'update']);
            Route::delete('delete/{uuid}', [PlanogramController::class, 'destroy']);
            Route::post('bulk-upload', [PlanogramController::class, 'bulkUpload']);
            Route::get('export', [PlanogramController::class, 'export']);
            Route::post('getshelf', [PlanogramController::class, 'getShelvesByCustomerIds']);
            Route::get('/merchendisher-list', [PlanogramController::class, 'listMerchendishers']);
            Route::post('getshelf', [PlanogramController::class, 'getShelvesByCustomerIds']);
            Route::get('/export-file', [PlanogramController::class, 'exportplanogram']);
        });
        Route::prefix('planogram-image')->group(function () {
            Route::get('list', [PlanogramImageController::class, 'index']);
            Route::get('show/{id}', [PlanogramImageController::class, 'show']);
            Route::post('create', [PlanogramImageController::class, 'store']);
            Route::post('update/{id}', [PlanogramImageController::class, 'update']);
            Route::delete('delete/{id}', [PlanogramImageController::class, 'destroy']);
            Route::post('bulk-upload', [PlanogramImageController::class, 'bulkUpload']);
            Route::get('export', [PlanogramImageController::class, 'export']);
        });
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
        Route::prefix('complaint-feedback')->group(function () {
            Route::get('list', [ComplaintFeedbackController::class, 'index']);
            Route::get('show/{uuid}', [ComplaintFeedbackController::class, 'show']);
            Route::post('create', [ComplaintFeedbackController::class, 'store']);
        });
        //Planogram Mobile Api
        Route::prefix('planogram-post')->group(function () {
            Route::post('create', [PlanogramPostController::class, 'create']);
            Route::get('list', [PlanogramPostController::class, 'index']);
            Route::get('exportfile', [PlanogramPostController::class, 'export']);
        });

        Route::prefix('complaint-feedback')->group(function () {
            Route::get('exportfile', [ComplaintFeedbackController::class, 'export']);
        });

        //  Route::prefix('campagin-info')->group(function () {
        //         Route::get('exportfile',[CampaignInformationController ::class, 'export']);
        // });

        //   Route::prefix('competitor-info')->group(function () {
        //      Route::get('exportfile',[CompetitorInfoController ::class, 'export']);
        // });
        Route::prefix('campagin-info')->group(function () {
            Route::get('exportfile', [CampaignInformationController::class, 'export']);
            Route::get('list', [CampaignInformationController::class, 'index']);
        });

        Route::prefix('competitor-info')->group(function () {
            Route::get('exportfile', [CompetitorInfoController::class, 'export']);
            Route::get('list', [CompetitorInfoController::class, 'index']);
            Route::get('show/{uuid}', [CompetitorInfoController::class, 'show']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::post('/codes/reserve', [CodeController::class, 'reserve'])->name('codes.reserve');
    Route::post('/codes/finalize', [CodeController::class, 'finalize'])->name('codes.finalize');
});
