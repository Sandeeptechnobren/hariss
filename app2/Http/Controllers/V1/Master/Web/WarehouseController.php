<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\WarehouseRequest;
use App\Services\V1\MasterServices\Web\WarehouseService;
use Illuminate\Http\Request;
use App\Http\Resources\V1\Master\Web\WarehouseResource;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use App\Exports\WarehousesExport;
use App\Models\Warehouse;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


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
        $perPage = request()->get('per_page', 10);

        $filters = request()->only([
            'warehouse_code', 'warehouse_name', 'owner_name', 'owner_number', 'owner_email',
            'company_customer_id', 'warehouse_manager', 'warehouse_manager_contact',
            'tin_no', 'registation_no', 'business_type', 'warehouse_type', 'city',
            'location', 'address', 'stock_capital', 'deposite_amount', 'region_id',
            'area_id', 'latitude', 'longitude', 'device_no', 'p12_file', 'password',
            'branch_id', 'is_branch', 'invoice_sync', 'status', 'is_efris',
            'created_user', 'updated_user', 'created_date_from', 'created_date_to'
        ]);

        $warehouses = $this->warehouseService->getAll($perPage, $filters);
        return $this->success(
            // $warehouses->items(),
            WarehouseResource::collection($warehouses->items()),

            'Success',
            200,
            [
                'pagination' => [
                    'current_page' => $warehouses->currentPage(),
                    'last_page'    => $warehouses->lastPage(),
                    'per_page'     => $warehouses->perPage(),
                    'total'        => $warehouses->total(),
                ]
            ]
        );
    } catch (\Exception $e) {
        return $this->fail($e->getMessage(), 500);
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
// public function store(WarehouseRequest $request): JsonResponse
//     {
//         $warehouse = $this->warehouseService->create($request->validated());
//         return response()->json(['code'=>200, 'success' => true, 'data' => $warehouse], 201);
//     }
public function store(WarehouseRequest $request): JsonResponse
{
    $data = $request->validated();

    if ($request->hasFile('p12_file')) {
        $file = $request->file('p12_file');
        $path = $file->store('public/p12_files');
        $data['p12_file'] = str_replace('public/', '', $path);
    }
    $warehouse = $this->warehouseService->create($data);
    return response()->json(['code'=>200, 'success' => true, 'data' => $warehouse], 201);
}
    /**
     * @OA\Get(
     *     path="/api/master/warehouse/{id}",
     *     summary="Get warehouse by ID",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Warehouse ID"),
     *     @OA\Response(response=200, description="Warehouse details",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */
    public function show($id): JsonResponse
    {
        $warehouse = $this->warehouseService->find($id);
        return response()->json(['success' => true, 'data' => $warehouse]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/warehouse/{id}",
     *     summary="Update a warehouse",
     *     tags={"Warehouses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Warehouse ID"),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse")
     *     ),
     *     @OA\Response(response=200, description="Updated warehouse",
     *         @OA\JsonContent(ref="#/components/schemas/Warehouse"))
     * )
     */

public function update(Request $request, $id): JsonResponse
{
    $rules = [
        'warehouse_code' => [
            'sometimes',
            'alpha_num',
            'max:20',
        ],
        'warehouse_type' => 'sometimes|string|in:company_outlet,agent_customer',
        'warehouse_name' => 'sometimes|string|min:3|max:50',
        'owner_name' => 'sometimes|string|max:50',
        'owner_number' => 'sometimes|numeric|digits_between:1,15',
        'owner_email' => 'sometimes|email|max:50',
        'agreed_stock_capital' => 'sometimes',
        'location' => 'sometimes|string|max:50',
        'city' => 'sometimes|string|max:25',
        'warehouse_manager' => 'sometimes|string|max:50',
        'warehouse_manager_contact' => 'sometimes|numeric|digits_between:1,20',
        'tin_no' => 'sometimes',
        'company' => 'sometimes|exists:tbl_company,id',
        'warehouse_email' => 'sometimes|email|max:50',
        'region_id' => 'sometimes|exists:tbl_region,id',
        'area_id' => 'sometimes|exists:tbl_areas,id',
        'latitude' => [
            'sometimes',
            'string',
            'max:50',
            'regex:/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/',
        ],
        'longitude' => [
            'sometimes',
            'string',
            'max:50',
            'regex:/^[-+]?((1[0-7]\d|[0-9]?\d)(\.\d+)?|180(\.0+)?)$/',
        ],
        'agent_customer' => 'sometimes|integer',
        'town_village' => 'nullable|string|max:50',
        'street' => 'nullable|string|max:50',
        'landmark' => 'nullable|string|max:50',
        'is_efris' => 'sometimes|in:0,1',
        'p12_file' => 'sometimes|max:100',
        'password' => 'sometimes|string|max:100',
        'is_branch' => 'sometimes|in:0,1',
        'branch_id' => 'sometimes|integer|max:100',
    ];
    $validated = $request->validate($rules);
    $warehouse = $this->warehouseService->update($id, $validated);

    return response()->json([
        'code' => 200,
        'success' => true,
        'data' => $warehouse,
    ]);
}


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
        return response()->json(['code'=>200,'success' => true, 'message' => 'Warehouse deleted successfully']);
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
    public function active(): JsonResponse
    {
        $warehouses = $this->warehouseService->getAllActive();
        return response()->json(['code'=>200,'success' => true, 'data' => $warehouses]);
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
        return response()->json(['code'=>200,'success' => true, 'data' => $warehouses]);
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
        return response()->json(['code'=>200,'success' => true, 'data' => $warehouse]);
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
        return response()->json(['code'=>200,'success' => true, 'data' => $warehouses]);
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
        return response()->json(['code'=>200,'success' => true, 'data' => $warehouses]);
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
                        'pagination' => [
                            'current_page' => $warehouses->currentPage(),
                            'last_page'    => $warehouses->lastPage(),
                            'per_page'     => $warehouses->perPage(),
                            'total'        => $warehouses->total(),
                        ]
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
public function exportWarehouses()
{
    $filters = request()->input('filters', []);
    $format = strtolower(request()->input('format', 'csv'));
    $filename = 'warehouses_' . now()->format('Ymd_His');
    $filePath = "exports/{$filename}";
    $query = Warehouse::query();
    if (!empty($filters)) {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where('warehouse_name', 'like', '%' . $filters['search'] . '%');
        }
    }
    if ($format === 'pdf') {
        $data = $query->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.warehouses', ['data' => $data]);
        $filePath .= '.pdf';
        \Storage::disk('public')->put($filePath, $pdf->output());
    } elseif ($format === 'xlsx') {
        $filePath .= '.xlsx';
        $export = new WarehousesExport($filters);
        $success = \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $filePath,
            'public',
            \Maatwebsite\Excel\Excel::XLSX
        );
        if (!$success) {
            throw new \Exception('Excel export failed.');
        }
    } else {
        $filePath .= '.csv';
        $export = new WarehousesExport($filters);
        $success = \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $filePath,
            'public',
            \Maatwebsite\Excel\Excel::CSV
        );
        if (!$success) {
            throw new \Exception('CSV export failed.');
        }
    }
    $downloadUrl = \Storage::disk('public')->url(basename($filePath) ? $filePath : "exports/" . basename($filePath));
    return response()->json(['url' => $downloadUrl], 200);
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

}
