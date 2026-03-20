<?php

use App\Http\Controllers\V1\Agent_Transaction\AgentDeliveryHeaderController;
use App\Http\Controllers\V1\Master\Web\AuthController;
use App\Http\Controllers\V1\Master\Web\MasterDataController;
use App\Http\Controllers\V1\Master\Web\AreaController;
use App\Http\Controllers\V1\Master\Web\CompanyController;
use App\Http\Controllers\V1\Master\Web\VehicleController;
use App\Http\Controllers\V1\Master\Web\CountryController;
use App\Http\Controllers\V1\Master\Web\RegionController;
use App\Http\Controllers\V1\Master\Web\WarehouseController;
use App\Http\Controllers\V1\Master\Web\ApprovalFlowController;
use App\Http\Controllers\V1\Master\Web\ApproverController;
use App\Http\Controllers\V1\Master\Web\ApprovalRequestController;
use App\Http\Controllers\V1\Master\Web\ApprovalStepController;
use App\Http\Controllers\V1\Master\Web\ApprovalActionController;
use App\Http\Controllers\V1\Master\Web\CompanyCustomerController;
use App\Http\Controllers\V1\Master\Web\ItemController;
use App\Http\Controllers\V1\Master\Web\SapItemController;
use App\Http\Controllers\V1\Master\Web\PricingHeaderController;
use App\Http\Controllers\V1\Master\Web\PromotionHeaderController;
use App\Http\Controllers\V1\Master\Web\PromotionDetailController;
use App\Http\Controllers\V1\Master\Web\PromotionGroupController;
use App\Http\Controllers\V1\Master\Web\RouteController;
use App\Http\Controllers\V1\Master\Web\RouteVisitController;
use App\Http\Controllers\V1\Master\Web\DriverController;
use App\Http\Controllers\V1\Master\Web\DiscountController;
use App\Http\Controllers\V1\Master\Web\RouteTransferController;
use App\Http\Controllers\V1\Agent_Transaction\LoadHeaderController;
use App\Http\Controllers\V1\Agent_Transaction\LoadDetailController;
use App\Http\Controllers\V1\Agent_Transaction\UnloadHeaderController;
use App\Http\Controllers\V1\Agent_Transaction\AdvancePaymentController;
use App\Http\Controllers\V1\Agent_Transaction\SalesmanWarehouseHistoryController;
use App\Http\Controllers\V1\Agent_Transaction\StockTransferListController;
use App\Http\Controllers\V1\Agent_Transaction\SalesmanReconsileController;
use App\Http\Controllers\V1\Agent_Transaction\SalesTeamTrackingController;
use App\Http\Controllers\V1\Agent_Transaction\OrderController;
use App\Http\Controllers\V1\Agent_Transaction\CollectionController;
use App\Http\Controllers\V1\Agent_Transaction\InvoiceController;
use App\Http\Controllers\V1\Agent_Transaction\ReturnController;
use App\Http\Controllers\V1\Agent_Transaction\CapsCollectionController;
use App\Http\Controllers\V1\Agent_Transaction\ExchangeController;
use App\Http\Controllers\V1\Agent_Transaction\RouteExpenceController;
use App\Http\Controllers\V1\Agent_Transaction\NewCustomerController;
use App\Http\Controllers\V1\Settings\Web\CustomerCategoryController;
use App\Http\Controllers\V1\Settings\Web\ProjectListController;
use App\Http\Controllers\V1\Settings\Web\ManufacturingController;
use App\Http\Controllers\V1\Settings\Web\TierController;
use App\Http\Controllers\V1\Settings\Web\CustomerSubCategoryController;
use App\Http\Controllers\V1\Settings\Web\CustomerTypeController;
use App\Http\Controllers\V1\Settings\Web\DeviceManagementController;
use App\Http\Controllers\V1\Settings\Web\UsertypesController;
use App\Http\Controllers\V1\Settings\Web\UserController;
use App\Http\Controllers\V1\Settings\Web\RewardCategoryController;
use App\Http\Controllers\V1\Settings\Web\ItemCategoryController;
use App\Http\Controllers\V1\Settings\Web\LabelController;
use App\Http\Controllers\V1\Settings\Web\BonusController;
use App\Http\Controllers\V1\Settings\Web\ItemSubCategoryController;
use App\Http\Controllers\V1\Settings\Web\SalesmanTypeController;
use App\Http\Controllers\V1\Settings\Web\BankController;
use App\Http\Controllers\V1\Settings\Web\ExpenseTypeController;
use App\Http\Controllers\V1\Settings\Web\SpareSubCategoryController;
use App\Http\Controllers\V1\Settings\Web\DiscountTypeController;
use App\Http\Controllers\V1\Settings\Web\OutletChannelController;
use App\Http\Controllers\V1\Settings\Web\PromotionTypeController;
use App\Http\Controllers\V1\Settings\Web\RouteTypeController;
use App\Http\Controllers\V1\Settings\Web\RoleController;
use App\Http\Controllers\V1\Settings\Web\SubMenuController;
use App\Http\Controllers\V1\Settings\Web\ExpenceTypeController;
use App\Http\Controllers\V1\Settings\Web\LocationController;
use App\Http\Controllers\V1\Settings\Web\BrandController;
use App\Http\Controllers\V1\Assets\Web\ChillerController;
use App\Http\Controllers\V1\Assets\Web\AgreementController;
use App\Http\Controllers\V1\Assets\Web\FrigeCustomerUpdateController;
use App\Http\Controllers\V1\Assets\Web\ChillerRequestController;
use App\Http\Controllers\V1\Assets\Web\IROHeaderController;
use App\Http\Controllers\V1\Assets\Web\IRController;
use App\Http\Controllers\V1\Assets\Web\BulkTransferRequestController;
use App\Http\Controllers\V1\Assets\Web\CallRegisterController;
use App\Http\Controllers\V1\Assets\Web\ServiceVisitController;
use App\Http\Controllers\V1\Assets\Web\ServiceTerritoryController;
use App\Http\Controllers\V1\Assets\Web\SpareController;
use App\Http\Controllers\V1\Assets\Web\VendorController;
use App\Http\Controllers\V1\Master\Web\AgentCustomerController;
use App\Http\Controllers\V1\Master\Web\PricingDetailController;
use App\Http\Controllers\V1\Master\Web\SalesmanController;
use App\Http\Controllers\V1\Settings\Web\CompanyTypeController;
use App\Http\Controllers\V1\Settings\Web\MenuController;
use App\Http\Controllers\V1\Settings\Web\ServiceTypeController;
use App\Http\Controllers\V1\Settings\Web\AssetTypeController;
use App\Http\Controllers\V1\Settings\Web\AssetManufacturerController;
use App\Http\Controllers\V1\Settings\Web\AssetModelNumberController;
use App\Http\Controllers\V1\Settings\Web\AssetBrandingController;
use App\Http\Controllers\V1\Settings\Web\FridgeStatusController;
use App\Http\Controllers\V1\Settings\Web\AssetsStatusController;
use App\Http\Controllers\V1\Merchendisher\Web\PlanogramController;
use App\Http\Controllers\V1\Merchendisher\Web\PlanogramImageController;
use App\Http\Controllers\V1\Merchendisher\Mob\PlanogramPostController;
use App\Http\Controllers\V1\Merchendisher\Web\ShelveController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyController;
use App\Http\Controllers\V1\Merchendisher\Web\SurveyQuestionController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyHeaderController;
use App\Http\Controllers\V1\Merchendisher\Mob\SurveyDetailController;
use App\Http\Controllers\V1\Merchendisher\Web\ComplaintFeedbackController;
use App\Http\Controllers\V1\Merchendisher\Mob\CampaignInformationController;
use App\Http\Controllers\V1\Merchendisher\Web\CompetitorInfoController;
use App\Http\Controllers\V1\Merchendisher\Web\ShelveItemController;
use App\Http\Controllers\V1\Merchendisher\Web\StockInStoreController;
use App\Http\Controllers\V1\Settings\Web\UomController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\CodeController;
use App\Http\Controllers\V1\ImportController;
use App\Http\Controllers\V1\Settings\Web\PermissionController;
use App\Http\Controllers\V1\Settings\Web\WarehouseStockController;
use App\Http\Controllers\V1\Settings\Web\SpareCategoryController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\POHeaderController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\OrderHeaderController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\DeliveryController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\HTInvoiceController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\HtReturnController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\CapsHController;
use App\Http\Controllers\V1\Hariss_Transaction\Web\TempReturnController;
use App\Http\Controllers\V1\Claim_Management\Web\CompiledClaimController;
use App\Http\Controllers\V1\Claim_Management\Web\PetitClaimController;
use App\Http\Controllers\V1\Approval_process\HtappWorkflowController;
use App\Http\Controllers\V1\Approval_process\HtappWorkflowApprovalController;
use App\Http\Controllers\V1\Approval_process\HtappWorkflowActionController;
use App\Http\Controllers\V1\Approval_process\HtappWorkflowReassignController;
use App\Http\Controllers\V1\Approval_process\HtappWorkflowStatusController;
use App\Http\Controllers\V1\Approval_process\ApprovalHistoryController;
use App\Http\Controllers\V1\Loyality_Management\LoyalityPointController;
use App\Http\Controllers\V1\Loyality_Management\AdjustmentController;
use App\Http\Controllers\V1\Ticket_Management\Web\RaiseTicketController;
use App\Http\Controllers\V1\SAP\SapItemSyncController;
use App\Http\Controllers\LogController;


// Route::get('/list', [OrderController::class, 'index']);
// Route::get('/{uuid}', [OrderController::class, 'show']);
Route::post('importdata', [ImportController::class, 'import']);
Route::prefix('master')->group(function () {
    // Route::post('auth/loginalert', [AuthController::class, 'login']);
    Route::post('auth/login', [AuthController::class, 'loginalert']);
    Route::middleware('auth:api')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::get('auth/checkEmail', [AuthController::class, 'checkUser']);
        Route::get('auth/checkPermission', [AuthController::class, 'checkPermission']);
        Route::post('auth/updateUser/{uuid}', [AuthController::class, 'updateUser']);
        Route::get('auth/getUserList', [AuthController::class, 'getUserList']);
        Route::get('auth/getUserbyUuid/{uuid}', [AuthController::class, 'getUserbyUuid']);
        Route::post('auth/unblock/{id}', [AuthController::class, 'unblockUser']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        Route::post('auth/tokenCheck', [AuthController::class, 'tokenCheck']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logoutall', [AuthController::class, 'logoutall']);
        Route::prefix('master-data')->group(function () {
            Route::get('/list', [MasterDataController::class, 'index']);
        });
        Route::prefix('company')->group(function () {
            Route::get('list_company', [CompanyController::class, 'index']);
            Route::get('global_search', [CompanyController::class, 'global_search']);
            Route::post('add_company', [CompanyController::class, 'store']);
            Route::get('company/{id}', [CompanyController::class, 'show']);
            Route::post('company/{id}', [CompanyController::class, 'update']);
            // Route::delete('company/{id}', [CompanyController::class, 'destroy']);
        });
        Route::prefix('approval')->group(function () {
            Route::post('workflow/save', [HtappWorkflowController::class, 'saveWorkflow']);
            Route::post('workflow/start', [HtappWorkflowApprovalController::class, 'startApproval']);
            Route::post('workflow/approve', [HtappWorkflowActionController::class, 'approve']);
            Route::post('workflow/editbefore-approval', [HtappWorkflowActionController::class, 'editbeforeapproval']);
            Route::post('workflow/reject',  [HtappWorkflowActionController::class, 'reject']);
            Route::post('workflow/return-back', [HtappWorkflowActionController::class, 'returnBack']);
            Route::post('workflow/reassign', [HtappWorkflowReassignController::class, 'reassign']);
            Route::get('workflow/status', [HtappWorkflowStatusController::class, 'getStatus']);
            Route::post('/order-process-flow', [HtappWorkflowStatusController::class, 'getOrderApprovalStatus']);
            Route::get('workflow/list', [HtappWorkflowController::class, 'list']);
            Route::post('workflow/update', [HtappWorkflowController::class, 'update']);
            Route::post('workflow/toggle', [HtappWorkflowController::class, 'toggle']);
            Route::get('workflow/detail/{uuid}', [HtappWorkflowController::class, 'getWorkflowByUuid']);
            Route::get('workflow/assigned-list', [HtappWorkflowController::class, 'getAssignedList']);
            Route::get('workflow/models', [HtappWorkflowController::class, 'getProcessTypes']);
            Route::get('workflow/myApprovals', [HtappWorkflowController::class, 'getMyApprovals']); //GET /htapp/workflow/requests?model=Order
            Route::get('workflow/requests', [HtappWorkflowController::class, 'getMyApprovalsByModel']); //GET /htapp/workflow/requests?model=Order
            Route::post('workflow/assign', [HtappWorkflowController::class, 'assign']);
            Route::get('workflow/assignments', [HtappWorkflowController::class, 'assignmentlist']);
            Route::put('workflow/updateAssignedWorkflow', [HtappWorkflowController::class, 'updateAssignedWorkflow']);
            // Route::put('workflow/changeAssignment', [HtappWorkflowController::class, 'updateAssignedWorkflow']);
            Route::get('workflow/myPermissions', [HtappWorkflowActionController::class, 'getMyPermissions']);
            Route::post('workflow/assignment/toggle', [HtappWorkflowController::class, 'toggleAssignment']);
            Route::get('history', [ApprovalHistoryController::class, 'index']);
        });
        Route::prefix('country')->group(function () {
            Route::get('list_country/', [CountryController::class, 'index']);
            Route::get('global_search', [CountryController::class, 'global_search']);
            Route::get('country/{id}', [CountryController::class, 'show']);
            Route::post('add_country/', [CountryController::class, 'store']);
            Route::put('update_country/{id}', [CountryController::class, 'update']);
            // Route::delete('/{id}', [CountryController::class, 'destroy']);
        });
        Route::prefix('region')->group(function () {
            Route::get('list_region/', [RegionController::class, 'index']);
            Route::get('region_dropdown/', [RegionController::class, 'regionDropdown']);
            Route::get('global_search', [RegionController::class, 'global_search']);
            Route::get('{id}', [RegionController::class, 'show']);
            Route::post('add_region/', [RegionController::class, 'store']);
            Route::put('update_region/{id}', [RegionController::class, 'update']);
            // Route::delete('/{id}', [RegionController::class, 'destroy']);
        });
        Route::prefix('area')->group(function () {
            Route::get('list_area', [AreaController::class, 'index']);
            Route::post('add_area', [AreaController::class, 'store']);
            Route::get('area/{id}', [AreaController::class, 'show']);
            Route::put('area/{id}', [AreaController::class, 'update']);
            // Route::delete('area/{id}', [AreaController::class, 'destroy']);
            Route::get('areadropdown', [AreaController::class, 'areaDropdown']);
            Route::get('global_search', [AreaController::class, 'global_search']);
        });
        Route::prefix('warehouse')->middleware('auth:api')->group(function () {
            Route::post('/export', [WarehouseController::class, 'exportWarehouses']);
            Route::get('/list', [WarehouseController::class, 'index']);
            Route::get('/getAllFilter', [WarehouseController::class, 'getAllFilter']);
            Route::get('/global_search', [WarehouseController::class, 'global_search']);
            Route::post('/create', [WarehouseController::class, 'store']);
            // Route::get('/{id}', [WarehouseController::class, 'show']);
            Route::get('{uuid}', [WarehouseController::class, 'show']);
            Route::put('/{uuid}', [WarehouseController::class, 'update']);
            // Route::delete('/{id}', [WarehouseController::class, 'destroy']);
            Route::get('/list_warehouse/active', [WarehouseController::class, 'active']);
            Route::get('/type/{type}', [WarehouseController::class, 'byType']);
            Route::put('/{id}/status', [WarehouseController::class, 'updateStatus']);
            Route::get('/region/{regionId}', [WarehouseController::class, 'byRegion']);
            Route::get('/area/{areaId}', [WarehouseController::class, 'byArea']);
            Route::post('/multiple_status_update', [WarehouseController::class, 'updateMultipleStatus']);
            Route::get('/warehouseCustomer/{id}', [WarehouseController::class, 'warehouseCustomer']);
            Route::get('/warehouseRoutes/{id}', [WarehouseController::class, 'warehouseRoutes']);
            Route::get('/warehouseVehicles/{id}', [WarehouseController::class, 'warehouseVehicles']);
            Route::get('/warehouseSalesman/{id}', [WarehouseController::class, 'warehouseSalesman']);
            Route::get('/{warehouse_id}/invoices', [WarehouseController::class, 'saleslist']);
            Route::get('/{warehouse_id}/returns', [WarehouseController::class, 'returnlist']);
        });
        Route::prefix('route-visits')->group(function () {
            Route::post('/export-all', [RouteVisitController::class, 'exportall']);
            Route::get('/dummy-csv', [RouteVisitController::class, 'downloadRouteVisitDummyCsv']);
            Route::post('/bulk-import', [RouteVisitController::class, 'bulkImport']);
            Route::get('list', [RouteVisitController::class, 'index']);
            Route::get('get_list', [RouteVisitController::class, 'list']);
            Route::get('global_search', [RouteVisitController::class, 'globlesearch']);
            Route::get('generate-code', [RouteVisitController::class, 'generateCode']);
            Route::post('/export', [RouteVisitController::class, 'exportall']);
            Route::get('/salesmen', [RouteVisitController::class, 'salesmanlist']);
            Route::get('customerlist/{merchandiser_id}', [RouteVisitController::class, 'getByMerchandiser']);
            Route::get('{uuid}', [RouteVisitController::class, 'show']);
            Route::post('add', [RouteVisitController::class, 'store']);
            Route::put('update/{uuid}', [RouteVisitController::class, 'update']);
            // Route::delete('{uuid}', [RouteVisitController::class, 'destroy']);
            Route::put('bulk-update', [RouteVisitController::class, 'bulkUpdate']);
        });
        Route::prefix('promotion-group')->group(function () {
            Route::get('list', [PromotionGroupController::class, 'index']);
            Route::post('create', [PromotionGroupController::class, 'store']);
            Route::get('show/{uuid}', [PromotionGroupController::class, 'show']);
            Route::put('update/{uuid}', [PromotionGroupController::class, 'update']);
            // Route::delete('delete/{uuid}', [PromotionGroupController::class, 'destroy']);
        });

        Route::prefix('companycustomer')->group(function () {
            Route::get('list', [CompanyCustomerController::class, 'index']);
            Route::get('/customers', [CompanyCustomerController::class, 'customer']);
            Route::post('/export', [CompanyCustomerController::class, 'export']);
            Route::get('show/{id}', [CompanyCustomerController::class, 'show']);
            Route::post('create', [CompanyCustomerController::class, 'store']);
            Route::put('{id}/update', [CompanyCustomerController::class, 'update']);
            // Route::delete('{id}/delete', [CompanyCustomerController::class, 'destroy']);
            Route::get('region/{regionId}', [CompanyCustomerController::class, 'getByRegion']);
            Route::get('area/{areaId}', [CompanyCustomerController::class, 'getByArea']);
            Route::get('active', [CompanyCustomerController::class, 'getActive']);
            Route::post('bulk-update-status', [CompanyCustomerController::class, 'bulkUpdateStatus']);
            Route::get('global-search', [CompanyCustomerController::class, 'globalSearch']);
            Route::get('customercompanytypebased', [CompanyCustomerController::class, 'activeTypeTwoCustomers']);
        });
        Route::prefix('vehicle')->group(function () {
            Route::get('/numberplate', [VehicleController::class, 'checkNumberPlate']);
            Route::get('/list', [VehicleController::class, 'index']);
            Route::get('global_search', [VehicleController::class, 'global_search']);
            Route::post('create', [VehicleController::class, 'store']);
            Route::put('{id}/update', [VehicleController::class, 'update']);
            // Route::delete('{id}/delete', [VehicleController::class, 'destroy']);
            Route::get('warehouse/{warehouseId}', [VehicleController::class, 'getByWarehouse']);
            Route::get('active', [VehicleController::class, 'getActive']);
            Route::post('export', [VehicleController::class, 'exportVehicles']);
            Route::post('/multiple_status_update', [VehicleController::class, 'updateMultipleStatus']);
            Route::get('{uuid}', [VehicleController::class, 'show']);
        });
        Route::prefix('route')->group(function () {
            Route::get('/list_routes', [RouteController::class, 'index']);
            Route::post('/export', [RouteController::class, 'export']);
            Route::post('/add_routes', [RouteController::class, 'store']);
            Route::get('/routes/{route}', [RouteController::class, 'show']);
            Route::put('/routes/{route}', [RouteController::class, 'update']);
            Route::get('global_search', [RouteController::class, 'global_search']);
            Route::post('bulk-update-status', [RouteController::class, 'bulkUpdateStatus']);
        });
        Route::prefix('agent_customers')->group(function () {
            Route::get('list', [AgentCustomerController::class, 'index']);
            Route::get('/global_search', [AgentCustomerController::class, 'global_search_agent_customer']);
            Route::get('agent-list', [AgentCustomerController::class, 'getAgent']);
            Route::post('/export', [AgentCustomerController::class, 'export']);
            Route::get('generate-code', [AgentCustomerController::class, 'generateCode']);
            Route::get('{uuid}', [AgentCustomerController::class, 'show']);
            Route::post('add/', [AgentCustomerController::class, 'store']);
            Route::put('update/{uuid}', [AgentCustomerController::class, 'update']);
            // Route::delete('{uuid}', [AgentCustomerController::class, 'destroy']);
            Route::post('bulk-update-status', [AgentCustomerController::class, 'bulkUpdateStatus']);
            Route::get('warehouse/{warehouseId}', [AgentCustomerController::class, 'getAgentCustomersByWarehouse']);
            Route::get('route/{routeid}', [AgentCustomerController::class, 'getAgentCustomersByroute']);
        });
        Route::prefix('items')->group(function () {
            Route::get('/item-exportReturn', [ItemController::class, 'exportReturn']);
            Route::get('/item-exportInvoice', [ItemController::class, 'exportItemInvoices']);
            Route::get('global-search', [ItemController::class, 'globalSearch']);
            Route::get('/category-wise-items', [ItemController::class, 'getItems']);
            Route::get('list/', [ItemController::class, 'index']);
            Route::post('/export', [ItemController::class, 'export']);
            Route::get('sap/import-item', [SapItemController::class, 'importFromSAP']);
            // Route::get('/global_search', [ItemController::class, 'global_search_items']);
            Route::get('{uuid}', [ItemController::class, 'show']);
            Route::post('add/', [ItemController::class, 'store']);
            Route::post('update/{uuid}', [ItemController::class, 'update']);
            // Route::delete('{id}', [ItemController::class, 'destroy']);
            Route::post('update-status', [ItemController::class, 'updateMultipleItemStatus']);
            Route::get('/item-invoices/{id}', [ItemController::class, 'getItemInvoices']);
            Route::get('/item-returns/{id}', [ItemController::class, 'getItemReturns']);
        });
        Route::prefix('salesmen')->group(function () {
            Route::get('getattendance', [SalesmanController::class, 'report']);
            Route::get('list', [SalesmanController::class, 'index']);
            Route::post('exportfile', [SalesmanController::class, 'exportSalesmen']);
            Route::post('bulk-upload', [SalesmanController::class, 'bulkUpload']);
            Route::get('generate-code', [SalesmanController::class, 'generateCode']);
            Route::get('global_search', [SalesmanController::class, 'global_search']);
            Route::post('update-status', [SalesmanController::class, 'updateMultipleSalesmanStatus']);
            Route::get('salesmanRoute/{uuid}', [SalesmanController::class, 'getSalesmanRoute']);
            Route::get('routeSalesman/{id}', [SalesmanController::class, 'getSalesmenByRoute']);
            Route::get('salespersalesman/{uuid}', [SalesmanController::class, 'salespersalesman']);
            Route::get('orderpersalesman/{uuid}', [SalesmanController::class, 'salesmanOrder']);
            Route::post('add', [SalesmanController::class, 'store']);
            Route::put('update/{uuid}', [SalesmanController::class, 'update']);
            Route::get('{uuid}', [SalesmanController::class, 'show']);
            Route::get('export-invoices/{uuid}', [SalesmanController::class, 'exportSalesmanInvoices']);
            Route::get('/orders-export/{salesman_uuid}', [SalesmanController::class, 'export']);
            Route::get('/po-export/{salesman_uuid}', [SalesmanController::class, 'po_export']);
        });

        Route::prefix('pricing-headers')->group(function () {
            Route::get('list/', [PricingHeaderController::class, 'index']);
            Route::get('generate-code', [PricingHeaderController::class, 'generateCode']);
            Route::get('{uuid}', [PricingHeaderController::class, 'show']);
            Route::post('add/', [PricingHeaderController::class, 'store']);
            Route::put('update/{uuid}', [PricingHeaderController::class, 'update']);
            Route::post('getItemPrice', [PricingHeaderController::class, 'getItemPrice']);

            // Route::delete('{uuid}', [PricingHeaderController::class, 'destroy']);getItemPrice
        });
        Route::prefix('pricing-details')->group(function () {
            Route::get('list', [PricingDetailController::class, 'index']);
            Route::get('global_search', [PricingDetailController::class, 'global_search']);
            Route::get('generate-code', [PricingDetailController::class, 'generateCode']);
            Route::get('{uuid}', [PricingDetailController::class, 'show']);
            Route::post('add', [PricingDetailController::class, 'store']);
            Route::put('update/{uuid}', [PricingDetailController::class, 'update']);
            // Route::delete('{uuid}', [PricingDetailController::class, 'destroy']);
        });
        Route::prefix('promotion-headers')->group(function () {
            Route::get('/global-search', [PromotionHeaderController::class, 'globalSearch']);
            Route::post('/applicable', [PromotionHeaderController::class, 'getAppliedPromotions']);
            Route::get('list', [PromotionHeaderController::class, 'index']);
            Route::post('create', [PromotionHeaderController::class, 'store']);
            Route::get('/warehouse', [PromotionHeaderController::class, 'getByWarehouse']);
            Route::get('show/{uuid}', [PromotionHeaderController::class, 'show']);
            Route::put('/{uuid}', [PromotionHeaderController::class, 'update']);
            Route::post('customers/upload-xlsx', [PromotionHeaderController::class, 'upload']);
            Route::get('customerdetails', [PromotionHeaderController::class, 'getCustomerDetails']);
            // Route::delete('delete/{id}', [PromotionHeaderController::class, 'destroy']);
        });
        Route::prefix('promotion-details')->group(function () {
            Route::get('list', [PromotionDetailController::class, 'index']);
            Route::post('create', [PromotionDetailController::class, 'store']);
            Route::get('show/{uuid}', [PromotionDetailController::class, 'show']);
            Route::put('update/{uuid}', [PromotionDetailController::class, 'update']);
            // Route::delete('delete/{uuid}', [PromotionDetailController::class, 'destroy']);
        });
        Route::prefix('discount')->group(function () {
            Route::get('/global-search', [DiscountController::class, 'globalSearch']);
            Route::get('list', [DiscountController::class, 'index']);
            Route::get('/{uuid}', [DiscountController::class, 'show']);
            Route::post('create', [DiscountController::class, 'store']);
            Route::put('/{uuid}', [DiscountController::class, 'update']);
            // Route::delete('delete/{uuid}', [DiscountController::class, 'destroy']);
            Route::get('global_search', [DiscountController::class, 'global_search']);
            Route::post('status-update', [DiscountController::class, 'updateStatus']);
            Route::get('export', [DiscountController::class, 'export']);
        });
        Route::prefix('route-transfer')->group(function () {
            Route::post('/transfer', [RouteTransferController::class, 'transfer']);
            Route::get('/history', [RouteTransferController::class, 'index']);
        });
    });
});

Route::prefix('settings')->group(function () {
    Route::middleware('auth:api')->group(function () {
        // Route::prefix('roles')->group(function () {
        //     Route::get('list', [RoleController::class, 'index']);
        //     Route::get('{id}', [RoleController::class, 'show']);
        //     Route::post('add', [RoleController::class, 'store']);
        //     Route::put('{id}', [RoleController::class, 'update']);
        //     Route::delete('{id}', [RoleController::class, 'destroy']);
        // });
        Route::prefix('banks')->group(function () {
            Route::get('list', [BankController::class, 'index']);
            Route::get('show/{uuid}', [BankController::class, 'show']);
            Route::post('create', [BankController::class, 'store']);
            Route::put('update/{uuid}', [BankController::class, 'update']);
        });
        Route::prefix('sparecategory')->group(function () {
            Route::get('list', [SpareCategoryController::class, 'index']);
            Route::get('show/{uuid}', [SpareCategoryController::class, 'show']);
            Route::post('create', [SpareCategoryController::class, 'store']);
            Route::put('update/{uuid}', [SpareCategoryController::class, 'update']);
            Route::delete('delete/{uuid}', [SpareCategoryController::class, 'destroy']);
        });
        Route::prefix('sparesubcategory')->group(function () {
            Route::get('list', [SpareSubCategoryController::class, 'index']);
            Route::get('show/{uuid}', [SpareSubCategoryController::class, 'show']);
            Route::post('create', [SpareSubCategoryController::class, 'store']);
            Route::put('update/{uuid}', [SpareSubCategoryController::class, 'update']);
            Route::delete('delete/{uuid}', [SpareSubCategoryController::class, 'destroy']);
        });
        Route::prefix('rewards')->group(function () {
            Route::get('list', [RewardCategoryController::class, 'index']);
            Route::get('show/{uuid}', [RewardCategoryController::class, 'show']);
            Route::post('create', [RewardCategoryController::class, 'store']);
            Route::put('update/{uuid}', [RewardCategoryController::class, 'update']);
            Route::delete('delete/{uuid}', [RewardCategoryController::class, 'destroy']);
        });
        Route::prefix('tiers')->group(function () {
            Route::get('list', [TierController::class, 'index']);
            Route::get('show/{uuid}', [TierController::class, 'show']);
            Route::post('create', [TierController::class, 'store']);
            Route::put('update/{uuid}', [TierController::class, 'update']);
            Route::delete('delete/{uuid}', [TierController::class, 'destroy']);
            Route::post('customertier', [TierController::class, 'updateCustomerTier']);
        });
        Route::prefix('bonus')->group(function () {
            Route::get('list', [BonusController::class, 'index']);
            Route::get('show/{uuid}', [BonusController::class, 'show']);
            Route::post('create', [BonusController::class, 'store']);
            Route::put('update/{uuid}', [BonusController::class, 'update']);
            Route::delete('delete/{uuid}', [BonusController::class, 'destroy']);
        });
        // Route::prefix('permissions')->group(function () {
        //     Route::get('list', [PermissionController::class, 'index']);         // GET all permissions
        //     Route::get('{id}', [PermissionController::class, 'show']);  // GET single permission
        //     Route::post('add', [PermissionController::class, 'store']);         // POST create permission
        //     Route::put('{id}', [PermissionController::class, 'update']); // PUT update permission
        //     Route::delete('{id}', [PermissionController::class, 'destroy']); // DELETE permission
        // });
        Route::prefix('locations')->group(function () {
            Route::put('/update/{uuid}', [LocationController::class, 'update']);
            Route::get('/list', [LocationController::class, 'index']);
            Route::get('/{uuid}', [LocationController::class, 'show']);
            Route::post('/add', [LocationController::class, 'store']);
            Route::delete('/delete/{uuid}', [LocationController::class, 'destroy']);
        });
        Route::prefix('devicemanagements')->group(function () {
            Route::put('/update/{uuid}', [DeviceManagementController::class, 'update']);
            Route::get('/list', [DeviceManagementController::class, 'index']);
            Route::get('show/{uuid}', [DeviceManagementController::class, 'show']);
            Route::post('/add', [DeviceManagementController::class, 'store']);
        });
        Route::prefix('manufacturings')->group(function () {
            Route::put('/update/{uuid}', [ManufacturingController::class, 'update']);
            Route::get('/list', [ManufacturingController::class, 'index']);
            Route::get('show/{uuid}', [ManufacturingController::class, 'show']);
            Route::post('/add', [ManufacturingController::class, 'store']);
        });
        Route::prefix('brands')->group(function () {
            Route::get('/list', [BrandController::class, 'index']);
            Route::get('show/{uuid}', [BrandController::class, 'show']);
            Route::post('/add', [BrandController::class, 'store']);
            Route::put('/update/{uuid}', [BrandController::class, 'update']);
        });
        Route::prefix('drivers')->group(function () {
            Route::get('/list', [DriverController::class, 'index']);
            Route::get('show/{uuid}', [DriverController::class, 'show']);
            Route::post('/add', [DriverController::class, 'store']);
            Route::put('/update/{uuid}', [DriverController::class, 'update']);
        });
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
            Route::get('/get-outlet-based', [OutletChannelController::class, 'getHierarchy']);
            Route::get('/{id}', [OutletChannelController::class, 'show']);
            Route::post('/', [OutletChannelController::class, 'store']);
            Route::put('/{id}', [OutletChannelController::class, 'update']);
            Route::delete('/{id}', [OutletChannelController::class, 'destroy']);
            Route::post('/status_update', [OutletChannelController::class, 'updateStatus']);
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

        Route::prefix('user')->group(function () {
            Route::get('/global-search', [UserController::class, 'globalSearch']);
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
            Route::get('get-profile', [RoleController::class, 'getAllData']);
            Route::get('getDropdownRole', [RoleController::class, 'getDropdownRole']);
            Route::post('/assign-permissions/{id}', [RoleController::class, 'assignPermissionsWithMenu']);
            Route::put('/permissions/{id}', [RoleController::class, 'updateRolePermissions']);
            Route::get('{id}', [RoleController::class, 'show']);
            Route::post('add', [RoleController::class, 'store']);
            Route::get('global-search', [RoleController::class, 'globalSearch']);
            Route::put('{id}', [RoleController::class, 'update']);
            Route::delete('{id}', [RoleController::class, 'destroy']);
        });
        Route::prefix('permissions')->group(function () {
            Route::get('submenubasedpermissons', [PermissionController::class, 'getBySubmenu']);
            Route::get('list', [PermissionController::class, 'index']);
            Route::get('{id}', [PermissionController::class, 'show']);
            Route::post('add', [PermissionController::class, 'store']);
            Route::put('{id}', [PermissionController::class, 'update']);
            Route::delete('{id}', [PermissionController::class, 'destroy']);
        });
        Route::prefix('uom')->group(function () {
            Route::get('list', [UomController::class, 'index']);
            Route::post('add', [UomController::class, 'store']);
            Route::get('{uuid}', [UomController::class, 'show']);
            Route::put('{uuid}', [UomController::class, 'update']);
            Route::delete('{uuid}', [UomController::class, 'destroy']);
        });

        Route::prefix('warehouse-stocks')->group(function () {
            Route::get('dayYesterdayMonthWisefilter', [WarehouseStockController::class, 'dayYesterdayMonthWisefilter']);
            Route::get('stockitemdetails', [WarehouseStockController::class, 'getItemsByWarehouse']);
            Route::get('/export', [WarehouseStockController::class, 'exportWarehouseStocks']);
            Route::get('{warehouseId}', [WarehouseStockController::class, 'LowStocks']);
            Route::get('stock-transfer/{warehouseId}', [WarehouseStockController::class, 'itemsByWarehouse']);
            Route::post('/transfer', [WarehouseStockController::class, 'bulkTransfer']);
            Route::get('list', [WarehouseStockController::class, 'index']);
            Route::get('/stock', [WarehouseStockController::class, 'checkStock']);
            Route::post('add', [WarehouseStockController::class, 'store']);
            Route::get('{uuid}', [WarehouseStockController::class, 'show']);
            Route::put('{uuid}', [WarehouseStockController::class, 'update']);
            Route::delete('{uuid}', [WarehouseStockController::class, 'destroy']);
            Route::get('warehouseStockInfo/{id}', [WarehouseStockController::class, 'warehouseStockInfo']);
            Route::get('/{warehouseId}/valuation', [WarehouseStockController::class, 'getWarehouseSummary']);
            // Route::get('/loaded-stock/{warehouseId}', [WarehouseStockController::class, 'getLoadedStock']);
            Route::get('/{warehouseId}/orders', [WarehouseStockController::class, 'getLatestOrders']);
            Route::get('/{warehouseId}/stock-details', [WarehouseStockController::class, 'getWarehouseStockDetails']);
            Route::get('/{warehouseId}/stock-helth', [WarehouseStockController::class, 'warehouseStockHealth']);
            Route::get('/itemsbasedwarehouse/{id}', [WarehouseStockController::class, 'getItemUomsByWarehouse']);
        });
        Route::prefix('labels')->group(function () {
            Route::get('list/', [LabelController::class, 'index']);
            Route::get('generate-code', [LabelController::class, 'generateCode']);
            Route::post('add/', [LabelController::class, 'store']);
            Route::get('{id}', [LabelController::class, 'show']);
            Route::put('{id}', [LabelController::class, 'update']);
            Route::delete('{id}', [LabelController::class, 'destroy']);
        });
        Route::prefix('expence-types')->group(function () {
            Route::get('list', [ExpenceTypeController::class, 'index']);
            Route::post('add', [ExpenceTypeController::class, 'store']);
            Route::get('{uuid}', [ExpenceTypeController::class, 'show']);
            Route::put('update/{uuid}', [ExpenceTypeController::class, 'update']);
            Route::delete('delete/{uuid}', [ExpenceTypeController::class, 'destroy']);
        });
        Route::prefix('projects-list')->group(function () {
            Route::get('/', [ProjectListController::class, 'index']);
            Route::post('/add', [ProjectListController::class, 'store']);
            Route::get('/{uuid}', [ProjectListController::class, 'show']);
            Route::put('/{uuid}', [ProjectListController::class, 'update']);
            Route::delete('/{uuid}', [ProjectListController::class, 'destroy']);
        });
        Route::prefix('asset-types')->group(function () {
            Route::post('/add', [AssetTypeController::class, 'store']);
            Route::get('/list', [AssetTypeController::class, 'index']);
        });
        Route::prefix('asset-manufacturer')->group(function () {
            Route::post('/add', [AssetManufacturerController::class, 'store']);
            Route::get('/list', [AssetManufacturerController::class, 'index']);
        });
        Route::prefix('asset-model-number')->group(function () {
            Route::post('/add', [AssetModelNumberController::class, 'store']);
            Route::get('/list', [AssetModelNumberController::class, 'index']);
        });
        Route::prefix('assets-branding')->group(function () {
            Route::get('/list', [AssetBrandingController::class, 'index']);
            Route::post('/add', [AssetBrandingController::class, 'store']);
        });
        Route::prefix('fridge-status')->group(function () {
            Route::get('/list', [FridgeStatusController::class, 'index']);
        });
        Route::prefix('assets-status')->group(function () {
            Route::get('/dropdown', [AssetsStatusController::class, 'dropdown']);
        });
    });
});

Route::prefix('assets')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('agreement')->group(function () {
            Route::post('export-pdf', [AgreementController::class, 'exportAgreementPdfById']);
        });
        Route::prefix('chiller')->group(function () {
            Route::post('import', [ChillerController::class, 'import']);
            Route::get('getupdatewarehouse_chiller', [ChillerController::class, 'transferindex']);
            Route::get('filterByStatus', [ChillerController::class, 'filterByStatus']);
            Route::post('filterData', [ChillerController::class, 'filterData']);
            Route::get('getByWarehouse', [ChillerController::class, 'getByWarehouse']);
            Route::post('transfer-chillers', [ChillerController::class, 'transfer']);
            Route::get('/get-chiller', [ChillerController::class, 'getChiller']);
            Route::get('/global-search', [ChillerController::class, 'globalSearch']);
            Route::post('/export', [ChillerController::class, 'exportChillers']);
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

        Route::prefix('spare')->group(function () {
            Route::get('list', [SpareController::class, 'index']);
            Route::get('show/{uuid}', [SpareController::class, 'show']);
            Route::post('create', [SpareController::class, 'store']);
            Route::put('update/{uuid}', [SpareController::class, 'update']);
            Route::delete('delete/{uuid}', [SpareController::class, 'destroy']);
        });
        Route::prefix('chiller-request')->group(function () {
            Route::get('/filter', [ChillerRequestController::class, 'filterChillerRequests']);
            Route::get('/approved', [ChillerRequestController::class, 'approvedChillerRequests']);
            Route::post('/crf-export', [ChillerRequestController::class, 'export']);
            Route::get('/export-chiller-request-pdf', [ChillerRequestController::class, 'exportChillerRequestPdf']);
            Route::get('list', [ChillerRequestController::class, 'index']);
            Route::post('getCRFData', [ChillerRequestController::class, 'getCRFData']);
            Route::post('globalFilter', [ChillerRequestController::class, 'globalFilter']);
            Route::get('global_search', [ChillerRequestController::class, 'global_search']);
            Route::get('generate-code', [ChillerRequestController::class, 'generateCode']);
            Route::get('{uuid}', [ChillerRequestController::class, 'show']);
            Route::post('add', [ChillerRequestController::class, 'store']);
            Route::post('{uuid}', [ChillerRequestController::class, 'update']);
            Route::delete('{uuid}', [ChillerRequestController::class, 'destroy']);
        });
        Route::prefix('iro')->group(function () {
            Route::get('/count', [IROHeaderController::class, 'getDetailCount']);
            Route::get('/list', [IROHeaderController::class, 'index']);
            Route::post('/add', [IROHeaderController::class, 'store']);
            Route::get('/{header_id}/{warehouse_id}', [IROHeaderController::class, 'getChillers']);
            Route::delete('/reverse/{id}', [IROHeaderController::class, 'reverse']);
            Route::get('/{id}', [IROHeaderController::class, 'show']);
        });
        Route::prefix('ir')->group(function () {
            Route::post('/export', [IRController::class, 'exportIR']);
            Route::post('/add', [IRController::class, 'store']);
            Route::get('/list', [IRController::class, 'index']);
            Route::get('/get-ir-list', [IRController::class, 'header']);
            Route::get('/iro', [IRController::class, 'getAllIRO']);
            Route::get('/salesman', [IRController::class, 'getAllSalesman']);
            Route::post('/reassign/{id}', [IRController::class, 'reassign']);
            Route::post('/reverse/{id}', [IRController::class, 'reverse']);
            Route::post('/{id}/close', [IrController::class, 'closeIr']);
            Route::get('/{uuid}', [IRController::class, 'show']);
        });
        Route::prefix('bulk-transfer')->group(function () {
            Route::get('/list', [BulkTransferRequestController::class, 'index']);
            Route::post('/add', [BulkTransferRequestController::class, 'store']);
            Route::get('/model-stock', [BulkTransferRequestController::class, 'countBySingleModel']);
            Route::get('/get-BTR', [BulkTransferRequestController::class, 'getByRegion']);
            Route::post('/allocate-assets', [BulkTransferRequestController::class, 'allocateAssets']);
            Route::get('/gteChillerByBTR/{id}', [BulkTransferRequestController::class, 'getWarehouseAndChillers']);
            Route::get('/model-numbers', [BulkTransferRequestController::class, 'getModelNumbers']);
            Route::get('/{uuid}', [BulkTransferRequestController::class, 'show']);
            Route::put('/{uuid}', [BulkTransferRequestController::class, 'update']);
            Route::delete('/{uuid}', [BulkTransferRequestController::class, 'destroy']);
            Route::get('/global-search', [BulkTransferRequestController::class, 'global_search']);
        });

        Route::prefix('call-register')->group(function () {
            Route::post('/export', [CallRegisterController::class, 'exportCallRegister']);
            Route::post('globalFilter', [CallRegisterController::class, 'globalFilter']);
            Route::get('/global_search', [CallRegisterController::class, 'global_search']);
            Route::get('/current-customer', [CallRegisterController::class, 'findCurrentCustomer']);
            Route::get('/list', [CallRegisterController::class, 'index']);
            Route::post('/add', [CallRegisterController::class, 'store']);
            Route::get('/chiller-data', [CallRegisterController::class, 'getChillerData']);
            Route::get('/{uuid}', [CallRegisterController::class, 'show']);
            Route::put('/{uuid}', [CallRegisterController::class, 'update']);
            Route::delete('/{id}', [CallRegisterController::class, 'destroy']);
        });
        Route::prefix('service-visit')->group(function () {
            Route::post('/export', [ServiceVisitController::class, 'export']);
            Route::post('/generate-code', [ServiceVisitController::class, 'generateCode']);
            Route::post('globalFilter', [ServiceVisitController::class, 'globalFilter']);
            Route::get('/list', [ServiceVisitController::class, 'index']);
            Route::post('/add', [ServiceVisitController::class, 'store']);
            Route::get('{uuid}', [ServiceVisitController::class, 'show']);
            Route::post('{uuid}', [ServiceVisitController::class, 'update']);
            Route::delete('{uuid}', [ServiceVisitController::class, 'destroy']);
        });
        Route::prefix('service-territory')->group(function () {
            Route::get('/export', [ServiceTerritoryController::class, 'exportTerritory']);
            Route::get('/list', [ServiceTerritoryController::class, 'index']);
            Route::post('/add', [ServiceTerritoryController::class, 'store']);
            Route::get('{uuid}', [ServiceTerritoryController::class, 'show']);
            Route::get('getViewData/{uuid}', [ServiceTerritoryController::class, 'getViewData']);
            Route::put('{uuid}', [ServiceTerritoryController::class, 'update']);
            Route::delete('{uuid}', [ServiceTerritoryController::class, 'destroy']);
        });
        Route::prefix('fridge-customer-update')->group(function () {
            Route::get('/list', [FrigeCustomerUpdateController::class, 'index']);
            Route::get('/export', [FrigeCustomerUpdateController::class, 'export']);
            Route::get('/global_search', [FrigeCustomerUpdateController::class, 'globalSearch']);
            Route::get('{uuid}', [FrigeCustomerUpdateController::class, 'show']);
            Route::post('update/{uuid}', [FrigeCustomerUpdateController::class, 'update']);
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
        Route::prefix('shelve_item')->group(function () {
            Route::get('list/{shelf_uuid}', [ShelveItemController::class, 'index']);
            Route::post('add', [ShelveItemController::class, 'store']);
            Route::get('show/{uuid}', [ShelveItemController::class, 'show']);
            Route::put('update/{uuid}', [ShelveItemController::class, 'update']);
            Route::delete('destroy/{uuid}', [ShelveItemController::class, 'destroy']);
            Route::get('damage-list/{shelf_uuid}', [ShelveItemController::class, 'damagelist']);
            Route::get('expiry-list/{shelf_uuid}', [ShelveItemController::class, 'expirylist']);
            Route::get('viewstock-list/{shelf_uuid}', [ShelveItemController::class, 'viewstock']);
        });
        Route::prefix('survey')->group(function () {
            Route::get('list', [SurveyController::class, 'index']);
            Route::post('add', [SurveyController::class, 'store']);
            Route::get('global-search', [SurveyController::class, 'globalSearch']);
            Route::get('/survey-export', [SurveyController::class, 'export']);
            Route::get('{uuid}', [SurveyController::class, 'show']);
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
            Route::post('update/{uuid}', [PlanogramController::class, 'update']);
            Route::delete('delete/{uuid}', [PlanogramController::class, 'destroy']);
            Route::post('bulk-upload', [PlanogramController::class, 'bulkUpload']);
            Route::get('export', [PlanogramController::class, 'export']);
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
            Route::get('show/{uuid}', [SurveyHeaderController::class, 'show']);
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
            Route::get('list/{planogram_uuid}', [PlanogramPostController::class, 'index']);
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
        Route::prefix('stockinstore')->group(function () {
            Route::post('create', [StockInStoreController::class, 'store']);
            Route::get('dropdownlistcustomers', [StockInStoreController::class, 'getDropdownList']);
            Route::get('list', [StockInStoreController::class, 'index']);
            Route::get('show/{uuid}', [StockInStoreController::class, 'show']);
            Route::put('update/{uuid}', [StockInStoreController::class, 'update']);
            Route::delete('delete/{uuid}', [StockInStoreController::class, 'destroy']);
            Route::post('bluckupload', [StockInStoreController::class, 'bulkUpload']);
            Route::get('/posts/{uuid}', [StockInStoreController::class, 'postsByStockUuid']);
        });
    });
});
Route::prefix('agent_transaction')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('load')->group(function () {
            Route::post('export', [LoadHeaderController::class, 'exportLoadHeader']);
            Route::get('getsalesmanbywarehouse', [LoadHeaderController::class, 'getSalesmanRouteByWarehouse']);
            Route::get('get-salesmanRoute', [LoadHeaderController::class, 'getSalesmanRoutes']);
            Route::post('exportcollapse', [LoadHeaderController::class, 'exportCollapseLoad']);
            Route::get('/list', [LoadHeaderController::class, 'index']);
            Route::post('/globalFilter', [LoadHeaderController::class, 'globalFilter']);
            Route::get('exportall', [LoadHeaderController::class, 'exportLoad']);
            Route::get('/{uuid}', [LoadHeaderController::class, 'show']);
            Route::post('/add', [LoadHeaderController::class, 'store']);
            Route::put('/update/{uuid}', [LoadHeaderController::class, 'update']);
            Route::delete('/{uuid}', [LoadHeaderController::class, 'destroy']);
            Route::get('/warehouse/{warehouse_id}/stock', [LoadHeaderController::class, 'getWarehouseStock']);
        });


        Route::prefix('orders')->group(function () {
            Route::get('export-item', [OrderController::class, 'exportInvoiceByItem']);
            Route::post('export', [OrderController::class, 'exportOrderHeader']);
            Route::post('exportall', [OrderController::class, 'exportOrders']);
            Route::post('exportcollapse', [OrderController::class, 'exportCollapseOrders']);
            Route::post('/globalFilter', [OrderController::class, 'globalFilter']);
            Route::get('/list', [OrderController::class, 'index']);
            Route::get('/{uuid}', [OrderController::class, 'show']);
            Route::get('/statistics', [OrderController::class, 'statistics']);
            Route::post('/add', [OrderController::class, 'store']);
            Route::put('/update/{uuid}', [OrderController::class, 'update']);
            // Route::delete('/{uuid}', [OrderController::class, 'destroy']);
            Route::post('/update-status', [OrderController::class, 'updateMultipleOrderStatus']);
        });
        Route::prefix('load-detail')->group(function () {
            Route::get('list', [LoadDetailController::class, 'index']);
            Route::post('add', [LoadDetailController::class, 'store']);
            Route::get('/{uuid}', [LoadDetailController::class, 'show']);
            Route::put('/{uuid}', [LoadDetailController::class, 'update']);
            Route::delete('/{uuid}', [LoadDetailController::class, 'destroy']);
        });
        Route::prefix('invoices')->group(function () {
            Route::get('exportinvoicewarehouse', [InvoiceController::class, 'exportInvoicesByWarehouse']);
            Route::get('exportinvoiceagentcustomer/{uuid}', [InvoiceController::class, 'exportInvoiceAgentCustomer']);
            Route::post('exportcollapse', [InvoiceController::class, 'exportInvoiceCollapse']);
            // Route::get('/filter', [InvoiceController::class, 'filter']);
            Route::get('list', [InvoiceController::class, 'index']);
            Route::post('/globalFilter', [InvoiceController::class, 'globalFilter']);
            Route::get('show/{uuid}', [InvoiceController::class, 'show']);
            Route::post('create', [InvoiceController::class, 'store']);
            Route::put('update/{uuid}', [InvoiceController::class, 'update']);
            Route::delete('delete/{uuid}', [InvoiceController::class, 'destroy']);
            Route::post('/updatestatus', [InvoiceController::class, 'updateMultipleOrderStatus']);
            Route::post('exportHeader', [InvoiceController::class, 'exportInvoiceHeader']);
            Route::get('exportall', [InvoiceController::class, 'exportInvoiceFullExport']);
            Route::get('agent-customer/{uuid}', [InvoiceController::class, 'getInvoicesByCustomerUuid']);
            Route::get('{warehouse_id}/exportheader', [InvoiceController::class, 'exportInvoiceByWarehouse']);
        });
        Route::prefix('returns')->group(function () {
            Route::get('/getInoviceBasedReturn', [ReturnController::class, 'getInoviceBasedReturn']);
            Route::get('exportreturnbasedwarehouse', [ReturnController::class, 'exportReturnsByWarehouse']);
            Route::get('getreturnbywarehouse', [ReturnController::class, 'getByWarehouse']);
            Route::get('list', [ReturnController::class, 'index']);
            Route::post('globalFilter', [ReturnController::class, 'globalFilter']);
            Route::get('show/{uuid}', [ReturnController::class, 'show']);
            Route::post('create', [ReturnController::class, 'store']);
            Route::put('update/{uuid}', [ReturnController::class, 'update']);
            Route::delete('delete/{uuid}', [ReturnController::class, 'destroy']);
            Route::post('/updatestatus', [ReturnController::class, 'updateMultipleOrderStatus']);
            Route::post('export', [ReturnController::class, 'exportReturnHeader']);
            Route::get('exportall', [ReturnController::class, 'exportReturnAll']);
            Route::post('exportcollapse', [ReturnController::class, 'exportReturnCollapse']);
            Route::get('exportcustomer', [ReturnController::class, 'exportReturnAgentCustomer']);
            Route::get('agent-customer/{uuid}', [ReturnController::class, 'getReturnsByCustomerUuid']);
            Route::get('agent-customer/export', [ReturnController::class, 'exportReturnAgentCustomer']);
            Route::get('/return_types', [ReturnController::class, 'returnlist']);
            Route::get('/reson', [ReturnController::class, 'resionlist']);
        });
        Route::prefix('capscollection')->group(function () {
            Route::get('exportall', [CapsCollectionController::class, 'exportCapsCollectionHeader']);
            Route::get('quantity', [CapsCollectionController::class, 'getQuantity']);
            Route::post('globalFilter', [CapsCollectionController::class, 'globalFilter']);
            Route::get('list', [CapsCollectionController::class, 'index']);
            Route::get('show/{uuid}', [CapsCollectionController::class, 'show']);
            Route::post('create', [CapsCollectionController::class, 'store']);
            Route::put('update/{uuid}', [CapsCollectionController::class, 'update']);
            Route::delete('delete/{uuid}', [CapsCollectionController::class, 'destroy']);
            Route::post('/updatestatus', [CapsCollectionController::class, 'updateMultipleOrderStatus']);
            Route::post('export', [CapsCollectionController::class, 'exportCapsCollection']);
            Route::post('exportcollapse', [CapsCollectionController::class, 'exportCapsCollapse']);
        });
        Route::prefix('unload')->group(function () {
            Route::post('exportcollapse', [UnloadHeaderController::class, 'exportUnloadCollapse']);
            Route::get('export', [UnloadHeaderController::class, 'exportUnloadHeader']);
            Route::get('exportall', [UnloadHeaderController::class, 'exportUnload']);
            Route::post('/add', [UnloadHeaderController::class, 'store']);
            Route::get('/list', [UnloadHeaderController::class, 'index']);
            Route::get('/getProjectSalesmanByRoute', [UnloadHeaderController::class, 'getProjectSalesmanByRoute']);
            Route::post('/globalFilter', [UnloadHeaderController::class, 'globalFilter']);
            Route::get('unload-data/{salesman_id}', [UnloadHeaderController::class, 'getUnloadData']);
            Route::get('/{uuid}', [UnloadHeaderController::class, 'show']);
            Route::put('/update/{uuid}', [UnloadHeaderController::class, 'update']);
            Route::delete('/{uuid}', [UnloadHeaderController::class, 'destroy']);
        });
        Route::prefix('exchanges')->group(function () {
            Route::post('/globalFilter', [ExchangeController::class, 'globalFilter']);
            Route::get('list', [ExchangeController::class, 'index']);
            Route::get('show/{uuid}', [ExchangeController::class, 'show']);
            Route::post('create', [ExchangeController::class, 'store']);
            Route::put('update/{uuid}', [ExchangeController::class, 'update']);
            Route::delete('delete/{uuid}', [ExchangeController::class, 'destroy']);
            Route::post('/updatestatus', [ExchangeController::class, 'updateMultipleOrderStatus']);
            Route::post('export', [ExchangeController::class, 'exportHeader']);
            Route::get('exportall', [ExchangeController::class, 'exportAll']);
            Route::get('exportallcollapse', [ExchangeController::class, 'exportAllCollapse']);
            Route::get('/export-pdf', [ExchangeController::class, 'exportExchangePdf']);
        });

        Route::prefix('route-expence')->group(function () {
            Route::get('list', [RouteExpenceController::class, 'index']);
            Route::post('add', [RouteExpenceController::class, 'store']);
            Route::get('{uuid}', [RouteExpenceController::class, 'show']);
            Route::put('update/{uuid}', [RouteExpenceController::class, 'update']);
            Route::delete('delete/{uuid}', [RouteExpenceController::class, 'destroy']);
        });
        Route::prefix('collections')->group(function () {
            Route::get('export', [CollectionController::class, 'exportCollection']);
            Route::get('list', [CollectionController::class, 'index']);
            Route::get('show/{uuid}', [CollectionController::class, 'show']);
            Route::post('create', [CollectionController::class, 'store']);
        });

        Route::prefix('agent-delivery')->group(function () {
            Route::post('/exportcollapse', [AgentDeliveryHeaderController::class, 'exportdeliverycollapse']);
            Route::get('/list', [AgentDeliveryHeaderController::class, 'index']);
            Route::post('/globalFilter', [AgentDeliveryHeaderController::class, 'globalFilter']);
            Route::post('/export', [AgentDeliveryHeaderController::class, 'exportHeaders']);
            Route::post('/exportall', [AgentDeliveryHeaderController::class, 'exportCapsCollectionfull']);
            Route::get('/{uuid}', [AgentDeliveryHeaderController::class, 'show']);
            Route::post('/add', [AgentDeliveryHeaderController::class, 'store']);
            Route::put('/update/{uuid}', [AgentDeliveryHeaderController::class, 'update']);
            Route::delete('/{uuid}', [AgentDeliveryHeaderController::class, 'destroy']);
        });
        Route::prefix('new-customer')->group(function () {
            Route::post('/globalFilter', [NewCustomerController::class, 'globalFilter']);
            Route::get('/generate-code', [NewCustomerController::class, 'generateCode']);
            Route::post('/export', [NewCustomerController::class, 'exportNewCustomer']);
            Route::get('/list', [NewCustomerController::class, 'index']);
            Route::get('/{uuid}', [NewCustomerController::class, 'show']);
            Route::post('/add', [NewCustomerController::class, 'store']);
            Route::put('/update/{uuid}', [NewCustomerController::class, 'update']);
            Route::delete('/{uuid}', [NewCustomerController::class, 'destroy']);
            // Route::post('/export', [NewCustomerController::class, 'export']);


        });
        Route::prefix('advancepayments')->group(function () {
            Route::post('export', [AdvancePaymentController::class, 'exportAdvancePaymentHeader']);
            Route::get('list', [AdvancePaymentController::class, 'index']);
            Route::get('show/{uuid}', [AdvancePaymentController::class, 'show']);
            Route::post('create', [AdvancePaymentController::class, 'store']);
            Route::put('update/{uuid}', [AdvancePaymentController::class, 'update']);
            Route::get('/company-customer/{id}', [AdvancePaymentController::class, 'getBankDetails']);
        });
        Route::prefix('salesman-warehouse-history')->group(function () {
            Route::get('/list', [SalesmanWarehouseHistoryController::class, 'index']);
            Route::post('/globalFilter', [SalesmanWarehouseHistoryController::class, 'globalFilter']);
        });
        Route::prefix('stock-transfer')->group(function () {
            Route::get('/list', [StockTransferListController::class, 'list']);
            Route::get('/{uuid}', [StockTransferListController::class, 'show']);
        });
        Route::prefix('reconsile')->group(function () {
            Route::get('/list', [SalesmanReconsileController::class, 'list']);
            Route::get('/get-data', [SalesmanReconsileController::class, 'index']);
            Route::post('/add', [SalesmanReconsileController::class, 'store']);
            Route::post('/salesman-block', [SalesmanReconsileController::class, 'block']);
            Route::get('/{uuid}', [SalesmanReconsileController::class, 'show']);
        });
        Route::prefix('salesteam-tracking')->group(function () {
            Route::get('/track', [SalesTeamTrackingController::class, 'show']);
        });
    });
});



Route::prefix('hariss_transaction')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('ht_caps')->group(function () {
            Route::post('globalFilter', [CapsHController::class, 'globalFilter']);
            Route::put('update/{uuid}', [CapsHController::class, 'update']);
            Route::get('export', [CapsHController::class, 'exportHtCaps']);
            Route::post('exportheader', [CapsHController::class, 'exportHtCapsHeader']);
            Route::get('exportcollapse', [CapsHController::class, 'exportcapsCollapse']);
            Route::get('list', [CapsHController::class, 'index']);
            Route::get('show/{uuid}', [CapsHController::class, 'show']);
            Route::post('create', [CapsHController::class, 'store']);
        });
        Route::prefix('po_orders')->group(function () {
            Route::post('/globalFilter', [POHeaderController::class, 'globalFilter']);
            Route::get('exportclitembsdpoorders', [POHeaderController::class, 'exportItembsPoOrderCollapse']);
            Route::get('itembsdpoorders', [POHeaderController::class, 'getByItem']);
            Route::get('cusbsdpuchorder', [POHeaderController::class, 'listByCustomer']);
            Route::get('export', [POHeaderController::class, 'exportPoOrders']);
            Route::post('exportheader', [POHeaderController::class, 'exportPoOrderHeader']);
            Route::post('exportcollapse', [POHeaderController::class, 'exportPoOrderCollapse']);
            Route::get('/list', [POHeaderController::class, 'index']);
            Route::get('/{uuid}', [POHeaderController::class, 'show']);
            Route::post('create', [POHeaderController::class, 'store']);
        });
        Route::prefix('ht_orders')->group(function () {
            Route::post('/globalFilter', [OrderHeaderController::class, 'globalFilter']);
            Route::get('export', [OrderHeaderController::class, 'exportHtOrders']);
            Route::post('exportheader', [OrderHeaderController::class, 'exportHtOrderHeader']);
            Route::post('exportcollapse', [OrderHeaderController::class, 'exportOrderCollapse']);
            Route::get('/list', [OrderHeaderController::class, 'index']);
            Route::get('/{uuid}', [OrderHeaderController::class, 'show']);
        });
        Route::prefix('ht_delivery')->group(function () {
            Route::post('/globalFilter', [DeliveryController::class, 'globalFilter']);
            Route::get('export', [DeliveryController::class, 'exportHtDelivery']);
            Route::post('exportheader', [DeliveryController::class, 'exportDeliveryHeader']);
            Route::post('exportcollapse', [DeliveryController::class, 'exportDeliveryCollapse']);
            Route::get('/list', [DeliveryController::class, 'index']);
            Route::get('/{uuid}', [DeliveryController::class, 'show']);
        });
        Route::prefix('ht_invoice')->group(function () {
            Route::post('globalFilter', [HTInvoiceController::class, 'globalFilter']);
            Route::get('invoicereturn', [HTInvoiceController::class, 'getFilteredInvoiceDetails']);
            Route::get('invoicebatch', [HTInvoiceController::class, 'filterByExpiry']);
            Route::get('/export', [HTInvoiceController::class, 'export']);
            Route::get('pdfexport', [HTInvoiceController::class, 'exportHtInvoice']);
            Route::post('exportheader', [HTInvoiceController::class, 'exportInvoiceHeader']);
            Route::post('exportcollapse', [HTInvoiceController::class, 'exportInvoiceCollapse']);
            Route::get('/list', [HTInvoiceController::class, 'index']);
            Route::get('/filter', [HTInvoiceController::class, 'filter']);
            Route::get('/{uuid}', [HTInvoiceController::class, 'show']);
        });
        Route::prefix('ht_returns')->group(function () {
            Route::post('globalFilter', [HtReturnController::class, 'globalFilter']);
            Route::get('export', [HtReturnController::class, 'exportHtReturns']);
            Route::post('exportheader', [HtReturnController::class, 'exportHtReturnHeader']);
            Route::post('create', [HtReturnController::class, 'store']);
            // Route::post('add', [HtReturnController::class, 'expliteData']);
            Route::post('exportcollapse', [HtReturnController::class, 'exportReturnCollapse']);
            Route::get('/list', [HtReturnController::class, 'index']);
            Route::get('show/{uuid}', [HtReturnController::class, 'show']);
            Route::post('batchfetch', [HtReturnController::class, 'fetchBatch']);
            Route::get('getwarehousestocks', [HtReturnController::class, 'getWarehouseStocks']);
        });
        Route::prefix('temp_returns')->group(function () {
            Route::get('export', [TempReturnController::class, 'exportTempReturn']);
            Route::get('exportheader', [TempReturnController::class, 'exportTempReturnHeader']);
            Route::get('exportcollapse', [TempReturnController::class, 'exportTempReturnCollapse']);
            Route::get('/list', [TempReturnController::class, 'index']);
            Route::get('show/{uuid}', [TempReturnController::class, 'show']);
            Route::get('gettemp', [TempReturnController::class, 'getTempReturnHeaders']);
        });
    });
});

Route::prefix('claim_management')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('compiled-claim')->group(function () {
            Route::post('/globalFilter', [CompiledClaimController::class, 'globalFilter']);
            Route::get('/list', [CompiledClaimController::class, 'index']);
            Route::get('/getAddCompiledData', [CompiledClaimController::class, 'getAddCompiledData']);
            Route::post('/add', [CompiledClaimController::class, 'store']);
            Route::post('/export', [CompiledClaimController::class, 'export']);
        });
        Route::prefix('petit-claim')->group(function () {
            Route::post('/globalFilter', [PetitClaimController::class, 'globalFilter']);
            Route::get('/list', [PetitClaimController::class, 'index']);
            Route::post('/add', [PetitClaimController::class, 'store']);
            Route::get(
                '/export',
                [PetitClaimController::class, 'export']
            );
        });
    });
});

Route::prefix('loyality_management')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('loyalitypoint')->group(function () {
            Route::get('list', [LoyalityPointController::class, 'index']);
            Route::get('show/{uuid}', [LoyalityPointController::class, 'show']);
            Route::post('create', [LoyalityPointController::class, 'store']);
            Route::put('update/{uuid}', [LoyalityPointController::class, 'update']);
            Route::delete('delete/{uuid}', [LoyalityPointController::class, 'destroy']);
            Route::post('getclosing', [LoyalityPointController::class, 'getClosing']);
            Route::post('getrewards', [LoyalityPointController::class, 'calculateRewards']);
            Route::post('getinvoices', [LoyalityPointController::class, 'getByCustomer']);
        });
        Route::prefix('adjustments')->group(function () {
            Route::get('list', [AdjustmentController::class, 'index']);
            Route::get('show/{uuid}', [AdjustmentController::class, 'show']);
            Route::post('create', [AdjustmentController::class, 'store']);
            Route::put('update/{uuid}', [AdjustmentController::class, 'update']);
            Route::delete('delete/{uuid}', [AdjustmentController::class, 'destroy']);
        });
    });
});

Route::prefix('Logs_Audit')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('logs')->group(function () {
            Route::get('list', [LogController::class, 'index']);
            Route::get('show/{id}', [LogController::class, 'show']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::post('/codes/reserve', [CodeController::class, 'reserve'])->name('codes.reserve');
    Route::post('/codes/finalize', [CodeController::class, 'finalize'])->name('codes.finalize');
});


Route::prefix('ticket_management')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::prefix('tickets')->group(function () {
            Route::get('getbyflag', [RaiseTicketController::class, 'getTicketsByUser']);
            Route::get('global-search', [RaiseTicketController::class, 'search']);
            Route::post('export', [RaiseTicketController::class, 'exportTickets']);
            Route::get('list', [RaiseTicketController::class, 'index']);
            Route::post('add', [RaiseTicketController::class, 'store']);
            Route::get('{uuid}', [RaiseTicketController::class, 'show']);
            Route::post('update/{id}', [RaiseTicketController::class, 'update']);
            Route::delete('delete/{id}', [RaiseTicketController::class, 'destroy']);
        });
    });
});


Route::prefix('SAP')->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::get('/item', [SapItemSyncController::class, 'sync']);
        Route::get('/test', [SapItemSyncController::class, 'test']);
    });
});
