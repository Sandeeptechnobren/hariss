<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\WarehouseRequest;
use App\Services\V1\MasterServices\Web\WarehouseService;
use Illuminate\Http\Request;
use App\Http\Resources\V1\Master\Web\WarehouseResource;
use App\Http\Resources\V1\Master\Web\RouteResource;
use App\Http\Resources\V1\Master\Web\SalesmanResource;
use App\Http\Resources\V1\Master\Web\AgentCustomerResource;
use App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderResource;
use App\Http\Resources\V1\Agent_Transaction\ReturnHeaderResource;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use App\Exports\WarehousesExport;
use App\Models\Warehouse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Helpers\ResponseHelper;
use App\Helpers\LogHelper;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;



/**
 * @OA\Tag(
 *     name="Warehouses",
 *     description="API Endpoints for managing warehouses"
 * )
 *
 * @OA\Schema(
 *     schema="Warehouse",
 *     type="object",
 *     required={
 *       "warehouse_code","warehouse_name","company_customer_id",
 *       "warehouse_manager","warehouse_manager_contact",
 *       "tin_no","registation_no","business_type","warehouse_type",
 *       "city","location","address","region_id","area_id",
 *       "latitude","longitude","device_no","p12_file",
 *       "password","status","created_user","updated_user"
 *     },
 *     @OA\Property(property="warehouse_code", type="string", example="WH0801"),
 *     @OA\Property(property="warehouse_name", type="string", example="Main Central Warehouse"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_number", type="string", example="9876543210"),
 *     @OA\Property(property="owner_email", type="string", example="owner@example.com"),
 *     @OA\Property(property="company_customer_id", type="integer", example=6),
 *     @OA\Property(property="warehouse_manager", type="string", example="Jane Smith"),
 *     @OA\Property(property="warehouse_manager_contact", type="string", example="9123456789"),
 *     @OA\Property(property="tin_no", type="string", example="TIN14423456789"),
 *     @OA\Property(property="registation_no", type="string", example="REG78987654"),
 *     @OA\Property(property="business_type", type="string", enum={"0","1"}, example="0"),
 *     @OA\Property(property="warehouse_type", type="string", enum={"0","1","2"}, example="1"),
 *     @OA\Property(property="city", type="string", example="Bangalore"),
 *     @OA\Property(property="location", type="string", example="Electronic City"),
 *     @OA\Property(property="address", type="string", example="123 MG Road"),
 *     @OA\Property(property="stock_capital", type="string", example="5000000"),
 *     @OA\Property(property="deposite_amount", type="string", example="1000000"),
 *     @OA\Property(property="region_id", type="integer", example=1),
 *     @OA\Property(property="district", type="string", example="Bangalore Urban"),
 *     @OA\Property(property="town_village", type="string", example="Electronic City"),
 *     @OA\Property(property="street", type="string", example="MG Road"),
 *     @OA\Property(property="landmark", type="string", example="Near Big Mall"),
 *     @OA\Property(property="latitude", type="string", example="12.93522345"),
 *     @OA\Property(property="longitude", type="string", example="77.62454678"),
 *     @OA\Property(property="threshold_radius", type="integer", example=500),
 *     @OA\Property(property="area_id", type="integer", example=3),
 *     @OA\Property(property="device_no", type="string", example="DEV123456"),
 *     @OA\Property(property="p12_file", type="string", example="warehouse_cert.p12"),
 *     @OA\Property(property="password", type="string", example="securepassword123"),
 *     @OA\Property(property="branch_id", type="string", example="BR001"),
 *     @OA\Property(property="is_branch", type="string", enum={"0","1"}, example="1"),
 *     @OA\Property(property="invoice_sync", type="string", enum={"0","1"}, example="1"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="is_efris", type="string", enum={"0","1"}, example="1"),
 *     @OA\Property(property="created_user", type="integer", example=4),
 *     @OA\Property(property="updated_user", type="integer", example=4),
 *     @OA\Property(property="created_date", type="string", format="date-time"),
 *     @OA\Property(property="updated_date", type="string", format="date-time")
 * )
 */
class WarehouseController extends Controller
{
    use ApiResponse;
    protected $warehouseService;
    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }
    private function convertNullToEmpty(&$data)
    {
        array_walk_recursive($data, function (&$value) {
            if ($value === null || $value === "NULL") {
                $value = "";
            }
        });
    }
    /**
     * @OA\Get(
     *     path="/api/master/warehouse/list",
     *     summary="Get all warehouses with filters & pagination",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), example=10, description="Number of records per page"),

     *     @OA\Parameter(name="warehouse_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="owner_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="owner_number", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="owner_email", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="company_customer_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouse_manager", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="warehouse_manager_contact", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="tin_no", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="registation_no", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="business_type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="warehouse_type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="city", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="location", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="address", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="stock_capital", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="deposite_amount", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="region_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="area_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="latitude", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="longitude", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="device_no", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="p12_file", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="password", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="branch_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_branch", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="invoice_sync", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1}), description="0=Inactive, 1=Active"),
     *     @OA\Parameter(name="is_efris", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of warehouses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Warehouse")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50),
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $allData  = filter_var(request()->get('allData', false), FILTER_VALIDATE_BOOLEAN);
            $perPage  = $allData ? null : request()->get('per_page', 50);
            $dropdown = filter_var(request()->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = request()->only([
                'region_id',
                'area_id',
                'status'
            ]);

            $warehouses = $this->warehouseService->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                $data = $warehouses->toArray();
                $this->convertNullToEmpty($data);
                return $this->success($data, 'Warehouse dropdown fetched successfully', 200);
            }

            if ($allData) {
                $data = WarehouseResource::collection($warehouses)->toArray(request());
                $this->convertNullToEmpty($data);

                return $this->success($data, 'Warehouses fetched successfully', 200);
            }

            $data = WarehouseResource::collection($warehouses->items())->toArray(request());
            $this->convertNullToEmpty($data);

            return $this->success(
                $data,
                'Warehouses fetched successfully',
                200,
                [
                    'current_page' => $warehouses->currentPage(),
                    'last_page'    => $warehouses->lastPage(),
                    'per_page'     => $warehouses->perPage(),
                    'total'        => $warehouses->total(),
                ]
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
    // public function index(): JsonResponse
    // {
    //     try {
    //         $allData  = filter_var(request()->get('allData', false), FILTER_VALIDATE_BOOLEAN);
    //         $perPage  = $allData ? null : request()->get('per_page', 50);
    //         $dropdown = filter_var(request()->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

    //         $filters = request()->only([
    //             'warehouse_id',
    //             'warehouse_code',
    //             'warehouse_name',
    //             'owner_name',
    //             'owner_number',
    //             'owner_email',
    //             'company_customer_id',
    //             'warehouse_manager',
    //             'warehouse_manager_contact',
    //             'tin_no',
    //             'registation_no',
    //             'business_type',
    //             'warehouse_type',
    //             'city',
    //             'location',
    //             'address',
    //             'stock_capital',
    //             'deposite_amount',
    //             'region_id',
    //             'area_id',
    //             'latitude',
    //             'longitude',
    //             'device_no',
    //             'p12_file',
    //             'branch_id',
    //             'is_branch',
    //             'invoice_sync',
    //             'status',
    //             'is_efris',
    //             'created_user',
    //             'updated_user',
    //             'created_date_from',
    //             'created_date_to'
    //         ]);

    //         $warehouses = $this->warehouseService->getAll($perPage, $filters, $dropdown);

    //         if ($dropdown) {
    //             $data = $warehouses->toArray();
    //             $this->convertNullToEmpty($data);
    //             return $this->success($data, 'Warehouse dropdown fetched successfully', 200);
    //         }

    //         if ($allData) {
    //             $data = WarehouseResource::collection($warehouses)->toArray(request());
    //             $this->convertNullToEmpty($data);

    //             return $this->success($data, 'Warehouses fetched successfully', 200);
    //         }

    //         $data = WarehouseResource::collection($warehouses->items())->toArray(request());
    //         $this->convertNullToEmpty($data);

    //         return $this->success(
    //             $data,
    //             'Warehouses fetched successfully',
    //             200,
    //             [
    //                 'current_page' => $warehouses->currentPage(),
    //                 'last_page'    => $warehouses->lastPage(),
    //                 'per_page'     => $warehouses->perPage(),
    //                 'total'        => $warehouses->total(),

    //             ]
    //         );
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }


    public function getAllFilter(): JsonResponse
    {
        try {
            $allData  = filter_var(request()->get('allData', false), FILTER_VALIDATE_BOOLEAN);
            $perPage  = $allData ? null : request()->get('per_page', 50);
            $dropdown = filter_var(request()->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = request()->only([
                'warehouse_code',
                'warehouse_name',
                'owner_name',
                'owner_number',
                'owner_email',
                'warehouse_manager',
                'warehouse_manager_contact',
                'tin_no',
                'warehouse_type',
                'city',
                'location',
                'region_id',
                'area_id',
                'branch_id',
                'is_branch',
                'is_efris',
                'status',
                'created_date_from',
                'created_date_to'
            ]);

            $warehouses = $this->warehouseService->getAllFilter($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'Warehouse dropdown fetched successfully',
                    'data'    => $warehouses,
                ]);
            }

            if ($allData) {
                return response()->json([
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'Warehouses fetched successfully',
                    'data'    => WarehouseResource::collection($warehouses),
                ]);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Warehouses fetched successfully',
                'data'    => WarehouseResource::collection($warehouses->items()),
                'pagination' => [
                    'current_page' => $warehouses->currentPage(),
                    'last_page'    => $warehouses->lastPage(),
                    'per_page'     => $warehouses->perPage(),
                    'total'        => $warehouses->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code'   => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/master/warehouse/create",
     *     summary="Create a new warehouse",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *      @OA\RequestBody(required=true,
     *      @OA\Property(property="status", type="string", example="success"),
     *      @OA\Property(property="code", type="integer", example=200),
     *      @OA\JsonContent(ref="#/components/schemas/Warehouse")
     *     ),
     *     @OA\Response(response=201, description="Warehouse created",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('p12_file')) {
            $file = $request->file('p12_file');

            // ✅ Store file
            $path = $file->store('p12_files', 'public');
            // result: p12_files/filename.pdf

            // ✅ Save in DB (relative path)
            $data['p12_file'] = $path;

            // ✅ Full URL generate
            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;

            $data['p12_file'] = $fullUrl; // optional
        }
        // dd($data);
        $warehouse = $this->warehouseService->create($data);

        if ($warehouse) {
            LogHelper::store(
                '16',
                '17',
                'add',
                null,
                $warehouse->getAttributes(),
                auth()->id()
            );
        }

        // ✅ ONLY CHANGE (use resource)
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => new WarehouseResource($warehouse)
        ], 201);
    }
    // public function store(WarehouseRequest $request): JsonResponse
    // {
    //     $data = $request->validated();
    //     // dd($data);
    //     if ($request->hasFile('p12_file')) {
    //         $file = $request->file('p12_file');
    //         $path = $file->store('public/p12_files');
    //         $data['p12_file'] = str_replace('public/', '', $path);
    //     }
    //     $warehouse = $this->warehouseService->create($data);

    //     if ($warehouse) {
    //         LogHelper::store(
    //             '16',
    //             '17',
    //             'add',
    //             null,
    //             $warehouse->getAttributes(),
    //             auth()->id()
    //         );
    //     }
    //     return response()->json(['code' => 200, 'success' => true, 'data' => $warehouse], 201);
    // }
    /**
     * @OA\Get(
     *     path="/api/master/warehouse/{uuid}",
     *     summary="Get warehouse by ID",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string"), description="Warehouse UUID"),
     *     @OA\Response(response=200, description="Warehouse details",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */
    public function show($uuid): JsonResponse
    {
        $warehouse = $this->warehouseService->find($uuid);

        // ✅ ONLY CHANGE (use resource)
        $warehouseArray = (new WarehouseResource($warehouse))->toArray(request());

        $this->convertNullToEmpty($warehouseArray);

        return $this->success($warehouseArray, 'Warehouse dropdown fetched successfully', 200);
    }
    // public function show($uuid): JsonResponse
    // {
    //     $warehouse = $this->warehouseService->find($uuid);
    //     $warehouseArray = $warehouse->toArray();
    //     $this->convertNullToEmpty($warehouseArray);
    //     return $this->success($warehouseArray, 'Warehouse dropdown fetched successfully', 200);
    // }
    /**
     * @OA\Put(
     *     path="/api/master/warehouse/{uuid}",
     *     summary="Update a warehouse",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string"), description="Warehouse UUID"),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse")
     *     ),
     *     @OA\Response(response=200, description="Updated warehouse",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */
    public function update(Request $request, $uuid): JsonResponse
    {
        $oldWarehouse = Warehouse::where('uuid', $uuid)->first();
        $previousData = $oldWarehouse ? $oldWarehouse->getOriginal() : null;
        $rules = [
            'warehouse_code' => ['sometimes', 'alpha_num', 'max:20'],
            'warehouse_type' => 'sometimes|integer|in:0,1',
            'warehouse_name' => 'sometimes|string|min:3|max:50',
            'owner_name' => 'sometimes|string|max:50',
            'owner_number' => 'nullable|numeric|digits_between:1,15',
            'owner_email' => 'nullable|email|max:50',
            'agreed_stock_capital' => 'sometimes',
            'location' => 'sometimes|string|max:50',
            'city' => 'sometimes|string|max:25',
            'warehouse_manager' => 'sometimes|string|max:50',
            'warehouse_manager_contact' => 'nullable|numeric|digits_between:1,20',
            'tin_no' => 'sometimes',
            'company' => 'sometimes|exists:tbl_company,id',
            'warehouse_email' => 'sometimes|email|max:50',
            'region_id' => 'nullable|exists:tbl_region,id',
            'area_id' => 'nullable|exists:tbl_areas,id',
            'latitude' => 'sometimes|string|max:50',
            'longitude' => 'sometimes|string|max:50',
            'agent_customer' => 'nullable|integer',
            'town_village' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:50',
            'is_efris' => 'sometimes|in:0,1',
            'p12_file' => 'sometimes|max:100',
            'device_no' => 'sometimes|max:100',
            'password' => 'nullable|string|max:100',
            'is_branch' => 'sometimes|in:0,1',
            'branch_id' => 'sometimes|integer|max:100',
        ];
        $validated = $request->validate($rules);
        $data = array_intersect_key($validated, $request->all());
        if (!empty($data['password'])) {
            $data['password'] = $data['password'];
        }
        if ($request->hasFile('p12_file')) {
            $file = $request->file('p12_file');
            $path = $file->store('p12_file', 'public');
            $data['p12_file'] = $path;
            $appUrl = rtrim(config('app.url'), '/');
            // $fullUrl = $appUrl . '/storage/app/public/' . $path;
            $fullUrl =  $path;

            $data['p12_file'] = $fullUrl; // optional
        }
        if (empty($data)) {
            $warehouse = $this->warehouseService->find($uuid);
        } else {
            $warehouse = $this->warehouseService->update($uuid, $data);
        }
        if ($warehouse && $previousData) {
            LogHelper::store(
                '16',
                '17',
                'update',
                $previousData,
                $warehouse->getAttributes(),
                auth()->id()
            );
        }
        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => new WarehouseResource($warehouse),
        ]);
    }
    // public function update(Request $request, $uuid): JsonResponse
    // {
    //     $oldWarehouse = Warehouse::where('uuid', $uuid)->first();
    //     $previousData = $oldWarehouse ? $oldWarehouse->getOriginal() : null;

    //     $rules = [
    //         'warehouse_code' => ['sometimes', 'alpha_num', 'max:20'],
    //         'warehouse_type' => 'sometimes|string|in:Distributor,Company Outlet',
    //         'warehouse_name' => 'sometimes|string|min:3|max:50',
    //         'owner_name' => 'sometimes|string|max:50',
    //         'owner_number' => 'nullable|numeric|digits_between:1,15',
    //         'owner_email' => 'nullable|email|max:50',
    //         'agreed_stock_capital' => 'sometimes',
    //         'location' => 'sometimes|string|max:50',
    //         'city' => 'sometimes|string|max:25',
    //         'warehouse_manager' => 'sometimes|string|max:50',
    //         'warehouse_manager_contact' => 'nullable|numeric|digits_between:1,20',
    //         'tin_no' => 'sometimes',
    //         'company' => 'sometimes|exists:tbl_company,id',
    //         'warehouse_email' => 'sometimes|email|max:50',
    //         'region_id' => 'nullable|exists:tbl_region,id',
    //         'area_id' => 'nullable|exists:tbl_areas,id',
    //         'latitude' => ['sometimes', 'string', 'max:50', 'regex:/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/'],
    //         'longitude' => ['sometimes', 'string', 'max:50', 'regex:/^[-+]?((1[0-7]\d|[0-9]?\d)(\.\d+)?|180(\.0+)?)$/'],
    //         'agent_customer' => 'nullable|integer',
    //         'town_village' => 'nullable|string|max:50',
    //         'street' => 'nullable|string|max:50',
    //         'landmark' => 'nullable|string|max:50',
    //         'is_efris' => 'sometimes|in:0,1',
    //         'p12_file' => 'sometimes|max:100',
    //         'device_no' => 'sometimes|max:100',
    //         'password' => 'nullable|string|max:100',
    //         'is_branch' => 'sometimes|in:0,1',
    //         'branch_id' => 'sometimes|integer|max:100',
    //     ];

    //     $validated = $request->validate($rules);

    //     $warehouse = $this->warehouseService->update($uuid, $validated);

    //     if ($warehouse && $previousData) {
    //         LogHelper::store(
    //             '16',
    //             '17',
    //             'update',
    //             $previousData,
    //             $warehouse->getAttributes(),
    //             auth()->id()
    //         );
    //     }

    //     return response()->json([
    //         'code' => 200,
    //         'success' => true,
    //         'data' => $warehouse,
    //     ]);
    // }


    // public function update(Request $request, $id): JsonResponse
    // {
    //     $rules = [
    //         'warehouse_code' => [
    //             'sometimes',
    //             'alpha_num',
    //             'max:20',
    //         ],
    //         'warehouse_type' => 'sometimes|string|in:Agent Warehouse,Company Outlet',
    //         'warehouse_name' => 'sometimes|string|min:3|max:50',
    //         'owner_name' => 'sometimes|string|max:50',
    //         'owner_number' => 'sometimes|numeric|digits_between:1,15',
    //         'owner_email' => 'sometimes|email|max:50',
    //         'agreed_stock_capital' => 'sometimes',
    //         'location' => 'sometimes|string|max:50',
    //         'city' => 'sometimes|string|max:25',
    //         'warehouse_manager' => 'sometimes|string|max:50',
    //         'warehouse_manager_contact' => 'sometimes|numeric|digits_between:1,20',
    //         'tin_no' => 'sometimes',
    //         'company' => 'sometimes|exists:tbl_company,id',
    //         'warehouse_email' => 'sometimes|email|max:50',
    //         'region_id' => 'nullable|exists:tbl_region,id',
    //         'area_id' => 'nullable|exists:tbl_areas,id',
    //         'latitude' => [
    //             'sometimes',
    //             'string',
    //             'max:50',
    //             'regex:/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/',
    //         ],
    //         'longitude' => [
    //             'sometimes',
    //             'string',
    //             'max:50',
    //             'regex:/^[-+]?((1[0-7]\d|[0-9]?\d)(\.\d+)?|180(\.0+)?)$/',
    //         ],
    //         'agent_customer' => 'nullable|integer',
    //         'town_village' => 'nullable|string|max:50',
    //         'street' => 'nullable|string|max:50',
    //         'landmark' => 'nullable|string|max:50',
    //         'is_efris' => 'sometimes|in:0,1',
    //         'p12_file' => 'sometimes|max:100',
    //         'password' => 'nullable|string|max:100',
    //         'is_branch' => 'sometimes|in:0,1',
    //         'branch_id' => 'sometimes|integer|max:100',
    //     ];
    //     $validated = $request->validate($rules);
    //     $warehouse = $this->warehouseService->update($id, $validated);

    //     return response()->json([
    //         'code' => 200,
    //         'success' => true,
    //         'data' => $warehouse,
    //     ]);
    // }


    /**
     * @OA\Delete(
     *     path="/api/master/warehouse/{id}",
     *     summary="Delete a warehouse",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Warehouse ID"),
     *     @OA\Response(response=200, description="Warehouse deleted")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $this->warehouseService->delete($id);
        return response()->json(['code' => 200, 'success' => true, 'message' => 'Warehouse deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/list_warehouse/active",
     *     summary="Get all active warehouses",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of active warehouses",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Warehouse"))
     *     )
     * )
     */
    public function active(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $warehouses = $this->warehouseService->getAllActive($search);

        return response()->json([
            'code' => 200,
            'success' => true,
            'data' => $warehouses
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/type/{type}",
     *     summary="Get warehouses by type",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string", enum={"0","1","2"})),
     *     @OA\Response(response=200, description="Warehouses by type",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Warehouse"))
     *     )
     * )
     */
    public function byType($type): JsonResponse
    {
        $warehouses = $this->warehouseService->getByType($type);
        return response()->json(['code' => 200, 'success' => true, 'data' => $warehouses]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/warehouse/{id}/status",
     *     summary="Update warehouse status",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status"},
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated status",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1']);
        $warehouse = $this->warehouseService->updateStatus($id, $request->status);
        return response()->json(['code' => 200, 'success' => true, 'data' => $warehouse]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/region/{regionId}",
     *     summary="Get warehouses by region",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="regionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouses by region",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Warehouse"))
     *     )
     * )
     */
    public function byRegion($regionId): JsonResponse
    {
        $warehouses = $this->warehouseService->getByRegion($regionId);
        return response()->json(['code' => 200, 'success' => true, 'data' => $warehouses]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/area/{areaId}",
     *     summary="Get warehouses by area",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="areaId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouses by area",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Warehouse"))
     *     )
     * )
     */
    public function byArea($areaId): JsonResponse
    {
        $warehouses = $this->warehouseService->getByArea($areaId);
        return response()->json(['code' => 200, 'success' => true, 'data' => $warehouses]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/global_search",
     *     summary="Search warehouses globally across multiple fields",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword to match across multiple fields like warehouse_code, warehouse_name, owner_name, region, area, company customer, etc."
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=10),
     *         description="Number of records per page"
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=1),
     *         description="Page number"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results with pagination",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Search results"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Warehouse")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */

    public function global_search(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $keyword = $request->get('query');

            $warehouses = $this->warehouseService->globalSearch($perPage, $keyword);

            return $this->success(
                $warehouses->items(),
                'Search results',
                200,
                [

                    'current_page' => $warehouses->currentPage(),
                    'last_page'    => $warehouses->lastPage(),
                    'per_page'     => $warehouses->perPage(),
                    'total'        => $warehouses->total(),

                ]
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\post(
     *     path="/api/master/warehouse/export",
     *     summary="Get warehouses by area",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Warehouse Export",
     *     )
     * )
     */
    public function exportWarehouses(Request $request)
    {
        $filters = $request->input('filters', []); // ✅ default empty array
        $format  = $request->input('format', 'xlsx');
        $search  = $request->input('search');

        $filename = 'warehouses_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        // ✅ FIX TYPO
        if ($format === 'xslx') {
            $format = 'xlsx';
        }

        $query = $this->buildWarehouseQuery($filters, $search);

        if ($format === 'pdf') {

            $data = $query->get();
            $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.warehouses', compact('data'));

            $filePath .= '.pdf';
            \Storage::disk('public')->put($filePath, $pdf->output());
        } elseif ($format === 'xlsx') {

            $filePath .= '.xlsx';
            \Maatwebsite\Excel\Facades\Excel::store(
                new WarehousesExport($query),
                $filePath,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );
        } else {

            $filePath .= '.csv';
            \Maatwebsite\Excel\Facades\Excel::store(
                new WarehousesExport($query),
                $filePath,
                'public',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return response()->json([
            'status' => 'success',
            'url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath
        ], 200);
        // return rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath;
    }
    private function buildWarehouseQuery(array $filters, ?string $search)
    {
        $query = Warehouse::with([
            'region:id,region_name',
            'area:id,area_name',
            'getCompany:id,company_name',
            'getCompanyCustomer:id,business_name',
        ]);

        $query = DataAccessHelper::filterWarehouses($query, Auth::user());

        /** 🔹 Global Search */
        if ($search) {
            $like = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(warehouse_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(warehouse_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(owner_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(tin_no) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(owner_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(city) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$like]);
            });
        }
        foreach ($filters as $field => $value) {

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($field, ['warehouse_code', 'warehouse_name'])) {

                $query->whereRaw(
                    "LOWER({$field}) LIKE ?",
                    ['%' . strtolower($value) . '%']
                );
            } elseif (in_array($field, ['region_id', 'area_id', 'company_customer_id'])) {

                $query->where($field, $value);
            } elseif ($field === 'status') {

                $query->where('status', $value);
            } else {

                $query->where($field, $value);
            }
        }

        return $query;
    }
    /**
     * @OA\Post(
     *     path="/api/master/warehouse/multiple_status_update",
     *     summary="Update status of multiple warehouses",
     *     description="Updates the status field for multiple warehouses by their IDs.",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"warehouse_ids","status"},
     *             @OA\Property(
     *                 property="warehouse_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 description="Array of warehouse IDs to update",
     *                 example={70, 72, 77, 80}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 description="New status to set",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Warehouse statuses updated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Update failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed.")
     *         )
     *     )
     * )
     */

    public function updateMultipleStatus(Request $request)
    {
        $request->validate([
            'warehouse_ids' => 'required|array|min:1',
            'warehouse_ids.*' => 'integer|exists:tbl_warehouse,id',
            'status' => 'required|integer',
        ]);
        $warehouseIds = $request->input('warehouse_ids');
        $status = $request->input('status');
        $result = $this->warehouseService->updateWarehousesStatus($warehouseIds, $status);
        if ($result) {
            return response()->json(['success' => true, 'message' => 'Warehouse statuses updated.'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }
    /**
     * @OA\get(
     *     path="/api/master/warehouse/warehouseCustomer/{id}",
     *     summary="Get warehouse Customers",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouse Customers",
     *     )
     * )
     */

    public function warehouseCustomer(Request $request, $id)
    {
        //  $perPage = $request->input('per_page', 10);
        $warehouseCustomers = $this->warehouseService->warehouseCustomer($request, $id);

        return ResponseHelper::paginatedResponse(
            'Warehouse Customer List',
            AgentCustomerResource::class,
            $warehouseCustomers
        );
    }

    /**
     * @OA\get(
     *     path="/api/master/warehouse/warehouseRoutes/{id}",
     *     summary="Get warehouse Routes",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouse Routes",
     *     )
     * )
     */

    public function warehouseRoutes(Request $request, $id)
    {

        $warehouseRoutes = $this->warehouseService->warehouseRoutes($request, $id);

        return ResponseHelper::paginatedResponse(
            'Warehouse Routes List',
            RouteResource::class,
            $warehouseRoutes
        );
    }
    /**
     * @OA\get(
     *     path="/api/master/warehouse/warehouseVehicles/{id}",
     *     summary="Get warehouse Vehicles",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouse Vehicles",
     *     )
     * )
     */
    public function warehouseVehicles(Request $request, $id)
    {
        $warehouseVehicles = $this->warehouseService->warehouseVehicles($id);
        return $this->success(
            $warehouseVehicles,
            'Warehouse Vehicles List',
            200,
        );
    }
    /**
     * @OA\get(
     *     path="/api/master/warehouse/warehouseSalesman/{id}",
     *     summary="Get warehouse Salesman",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Warehouse Salesman",
     *     )
     * )
     */
    public function warehouseSalesman(Request $request, $id)
    {
        // $perPage = $request->get('per_page', 10);
        $warehouseSalesmen = $this->warehouseService->warehouseSalesman($request, $id);

        return ResponseHelper::paginatedResponse(
            'Warehouse Salesman List',
            SalesmanResource::class,
            $warehouseSalesmen
        );
    }


    /**
     * @OA\Get(
     *     path="/api/master/warehouse/{warehouse_id}/invoices",
     *     tags={"Warehouses"},
     *     summary="Get invoice list by warehouse with optional salesman_code and route_code filters",
     *     description="Fetch a paginated list of invoices for a specific warehouse, optionally filtered by salesman_code or route_code.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="path",
     *         required=true,
     *         description="Warehouse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="salesman_code",
     *         in="query",
     *         required=false,
     *         description="Filter by Salesman Code (case-insensitive partial match)",
     *         @OA\Schema(type="string", example="SM001")
     *     ),
     *
     *     @OA\Parameter(
     *         name="route_code",
     *         in="query",
     *         required=false,
     *         description="Filter by Route Code (case-insensitive partial match)",
     *         @OA\Schema(type="string", example="RT001")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of results per page (default 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Invoices fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sales fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="invoice_number", type="string", example="INV-0012"),
     *                     @OA\Property(property="invoice_date", type="string", format="date", example="2025-11-12"),
     *                     @OA\Property(property="warehouse_id", type="integer", example=1),
     *                     @OA\Property(property="customer_name", type="string", example="Sharma Distributors"),
     *                     @OA\Property(property="salesman_code", type="string", example="SM001"),
     *                     @OA\Property(property="route_code", type="string", example="RT001"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=2500.50),
     *                     @OA\Property(
     *                         property="details",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="item_name", type="string", example="Product A"),
     *                             @OA\Property(property="qty", type="integer", example=2),
     *                             @OA\Property(property="price", type="number", example=500),
     *                             @OA\Property(property="total", type="number", example=1000)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 description="Pagination links"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Pagination meta information"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Missing required warehouse_id parameter",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="warehouse_id is required")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function saleslist(Request $request, $warehouse_id)
    {
        try {
            if (empty($warehouse_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'warehouse_id is required',
                ], 400);
            }
            $filters = $request->only(['salesman_code', 'route_code']);
            $perPage = $request->get('per_page', 50);
            $invoices = $this->warehouseService->getSales($warehouse_id, $filters, $perPage);
            return ResponseHelper::paginatedResponse(
                'Sales fetched successfully',
                InvoiceHeaderResource::class,
                $invoices
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function salesDownload(Request $request, $warehouse_id) {}

    /**
     * @OA\Get(
     *     path="/api/master/warehouse/{warehouse_id}/returns",
     *     tags={"Warehouses"},
     *     summary="Get return list by warehouse with optional salesman_code and route_code filters",
     *     description="Fetch a paginated list of returns filtered by warehouse (required via path) and optionally by salesman_code or route_code.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="path",
     *         required=true,
     *         description="Warehouse ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="salesman_code",
     *         in="query",
     *         required=false,
     *         description="Filter by Salesman Code (case-insensitive partial match)",
     *         @OA\Schema(type="string", example="SM001")
     *     ),
     *
     *     @OA\Parameter(
     *         name="route_code",
     *         in="query",
     *         required=false,
     *         description="Filter by Route Code (case-insensitive partial match)",
     *         @OA\Schema(type="string", example="RT001")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of results per page (default 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Returns fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Return fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=21),
     *                     @OA\Property(property="return_number", type="string", example="RET-0021"),
     *                     @OA\Property(property="return_date", type="string", format="date", example="2025-11-12"),
     *                     @OA\Property(property="warehouse_id", type="integer", example=1),
     *                     @OA\Property(property="customer_name", type="string", example="Patel Distributors"),
     *                     @OA\Property(property="salesman_code", type="string", example="SM002"),
     *                     @OA\Property(property="route_code", type="string", example="RT003"),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=1250.75),
     *                     @OA\Property(
     *                         property="details",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="item_name", type="string", example="Product B"),
     *                             @OA\Property(property="qty", type="integer", example=3),
     *                             @OA\Property(property="price", type="number", example=250),
     *                             @OA\Property(property="total", type="number", example=750)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 description="Pagination links"
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Pagination meta information"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function returnlist(Request $request, $warehouse_id)
    {
        try {
            if (empty($warehouse_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'warehouse_id is required',
                ], 400);
            }
            $filters = $request->only(['salesman_code', 'route_code']);
            $perPage = $request->get('per_page', 50);
            $returns = $this->warehouseService->getreturns($warehouse_id, $filters, $perPage);
            return ResponseHelper::paginatedResponse(
                'Return fetched successfully',
                ReturnHeaderResource::class,
                $returns
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }
}
