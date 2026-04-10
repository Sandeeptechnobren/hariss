<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Exports\AgentCustomerExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\AgentCustomerRequest;
use App\Http\Resources\V1\Master\Web\AgentCustomerDropdownResource;
use App\Http\Resources\V1\Master\Web\AgentCustomerResource;
use App\Services\V1\MasterServices\Web\AgentCustomerService;
use Illuminate\Http\JsonResponse;
use App\Models\AgentCustomer;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\LogHelper;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     schema="AgentCustomer",
 *     type="object",
 *     required={"route_id", "owner_name", "threshold_radius", "channel_id", "subcategory_id", "region_id", "area_id", "vat_no"},
 *     @OA\Property(property="name", type="string", example="AgentCustomer"),
 *     @OA\Property(property="business_name", type="string", example="My Business"),
 *     @OA\Property(property="customer_type", type="integer", example=0),
 *     @OA\Property(property="route_id", type="integer", example=1),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_no", type="string", example="9876543210"),
 *     @OA\Property(property="is_whatsapp", type="integer", example=1),
 *     @OA\Property(property="whatsapp_no", type="string", example="9876543210"),
 *     @OA\Property(property="email", type="string", example="customer@example.com"),
 *     @OA\Property(property="language", type="string", example="English"),
 *     @OA\Property(property="contact_no2", type="string", example="0123456789"),
 *     @OA\Property(property="buyertype", type="integer", example=0),
 *     @OA\Property(property="payment_type", type="integer", example=0),
 *     @OA\Property(property="creditday", type="integer", example=30),
 *     @OA\Property(property="tin_no", type="string", example="TIN123456"),
 *     @OA\Property(property="threshold_radius", type="integer", example=100),
 *     @OA\Property(property="outlet_channel_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=1),
 *     @OA\Property(property="region_id", type="integer", example=1),
 *     @OA\Property(property="area_id", type="integer", example=1),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 */
class AgentCustomerController extends Controller
{
    protected AgentCustomerService $service;

    public function __construct(AgentCustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/agent_customers/list",
     *     tags={"AgentCustomer"},
     *     summary="Get all agent customers with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="osa_code", in="query", description="Filter by customer code", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of agent customers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Customers fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/AgentCustomer")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */

    // public function index(Request $request): JsonResponse
    // {
    //     $filters = $request->only([
    //         'osa_code',
    //         'name',
    //         'owner_name',
    //         'business_name',
    //         'route_id',
    //         'outlet_channel_id',
    //         'category_id',
    //         'subcategory_id',
    //         'region_id',
    //         'area_id',
    //         'status',
    //         'warehouse'
    //     ]);
    //     $perPage = $request->get('limit', 10);
    //     $customers = $this->service->getAll($perPage, $filters);
    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'message'    => 'Customers fetched successfully',
    //         'data'       => AgentCustomerResource::collection($customers->items()),
    //         'pagination' => [
    //             'page'         => $customers->currentPage(),
    //             'limit'        => $customers->perPage(),
    //             'totalPages'   => $customers->lastPage(),
    //             'totalRecords' => $customers->total(),
    //         ]
    //     ]);
    // }
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'osa_code',
            'name',
            'owner_name',
            'route_id',
            'route_name',
            'outlet_channel_id',
            'outlet_channel_name',
            'category_id',
            'category_name',
            'subcategory_id',
            'warehouse_id',
            'subcategory_name',
            'region_id',
            'status',
            'customer_type'
        ]);
        foreach (
            [
                'subcategory_id',
                'category_id',
                'route_id',
                'region_id',
            ] as $key
        ) {
            if (!empty($filters[$key])) {
                $filters[$key] = is_array($filters[$key])
                    ? $filters[$key]
                    : array_map('intval', explode(',', $filters[$key]));
            }
        }
        $perPage = $request->get('limit', 50);
        $type = $request->get('type');
        $customers = $this->service->getAll($perPage, $filters, $type);
        return response()->json([
            'status'      => 'success',
            'code'        => 200,
            'message'     => 'Customers fetched successfully',
            'data'        => AgentCustomerResource::collection($customers->items()),
            'pagination'  => [
                'page'         => $customers->currentPage(),
                'limit'        => $customers->perPage(),
                'totalPages'   => $customers->lastPage(),
                'totalRecords' => $customers->total(),
            ]
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/master/agent_customers/agent-list",
     *     tags={"AgentCustomer"},
     *     summary="Get paginated customer list (id, osa_code, name)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated list retrieved successfully"),
     * )
     */
    public function getAgent(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $filters = $request->only(['osa_code', 'name']);
        $type    = $request->input('type');  // captures type=2

        $customers = $this->service->getList($perPage, $filters, $type);

        if ($customers->isEmpty()) {
            return response()->json([
                'status'  => 'fail',
                'code'    => 200,
                'message' => 'Customer not found',
                'data'    => null,
            ], 200);
        }

        return response()->json([
            'status'      => 'success',
            'code'        => 200,
            'message'     => 'Customers fetched successfully',
            'data'        => AgentCustomerDropdownResource::collection($customers->items()),
            'pagination'  => [
                'page'         => $customers->currentPage(),
                'limit'        => $customers->perPage(),
                'totalPages'   => $customers->lastPage(),
                'totalRecords' => $customers->total(),
            ],
        ], 200);
    }



    /**
     * @OA\Get(
     *     path="/api/master/agent_customers/{uuid}",
     *     tags={"AgentCustomer"},
     *     summary="Get a single agent customer by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer details", @OA\JsonContent(ref="#/components/schemas/AgentCustomer")),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $customer = $this->service->findByUuid($uuid);
        if (!$customer) {
            return response()->json([
                'status'  => 'fail',
                'code'    => 200,
                'message' => 'Customer not found',
                'data'    => null
            ], 404);
        }
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer fetched successfully',
            'data'    => $customer
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/agent_customers/add",
     *     tags={"AgentCustomer"},
     *     summary="Create a new agent customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AgentCustomer")
     *     ),
     *     @OA\Response(response=201, description="Customer created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(AgentCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $customer = $this->service->create($validated);
        LogHelper::store(
            '7',
            '20',
            'add',
            null,
            $customer->toArray(),
            auth()->id()
        );

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer created successfully',
            'data'    => $customer
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/master/agent_customers/update/{uuid}",
     *     tags={"AgentCustomer"},
     *     summary="Update an agent customer by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/AgentCustomer")),
     *     @OA\Response(response=200, description="Customer updated successfully"),
     *     @OA\Response(response=404, description="Customer not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'customer_type'     => 'sometimes|exists:customer_types,id',
            'warehouse'         => 'sometimes|exists:tbl_warehouse,id',
            'owner_name'        => 'sometimes|string|max:25',
            'route_id'          => 'sometimes|exists:tbl_route,id',
            'landmark'          => 'nullable|string',
            'district'          => 'nullable|string',
            'street'            => 'nullable|string',
            'town'              => 'nullable|string',
            'whatsapp_no'       => 'nullable|string|max:200',
            'contact_no'        => 'sometimes|string|max:20',
            'contact_no2'       => 'sometimes|string|max:20',
            'buyertype'         => 'sometimes|in:0,1',
            'payment_type'      => 'sometimes|in:1,2,3',
            'is_cash'           => 'sometimes|in:0,1',
            'vat_no'            => 'nullable|string',
            'creditday'         => 'sometimes|numeric',
            'credit_limit'      => 'nullable',
            'outlet_channel_id' => 'sometimes|exists:outlet_channel,id',
            'category_id'       => 'sometimes|exists:customer_categories,id',
            'subcategory_id'    => 'sometimes|exists:customer_sub_categories,id',
            'latitude'          => 'nullable|string',
            'longitude'         => 'nullable|string',
            'qr_code'           => 'nullable|string',
            'status'            => 'sometimes|integer|in:0,1',
            'enable_promotion'  => 'sometimes|integer|in:0,1',
        ]);
        $oldCustomer = $this->service->findByUuid($uuid);
        $previousData = $oldCustomer ? $oldCustomer->toArray() : null;
        $updated = $this->service->updateByUuid($uuid, $validated);
        $currentData = $updated ? $updated->toArray() : null;

        LogHelper::store(
            '7',
            '20',
            'update',
            $previousData,
            $currentData,
            auth()->id()
        );

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer updated successfully',
            'data'    => $updated,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/master/agent_customers/{uuid}",
     *     tags={"AgentCustomer"},
     *     summary="Soft delete an agent customer by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer deleted successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->service->deleteByUuid($uuid);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer deleted successfully',
            'data'    => null
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/agent_customers/generate-code",
     *     tags={"AgentCustomer"},
     *     summary="Generate unique customer code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unique customer code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique customer code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="osa_code", type="string", example="AC001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        $code = $this->service->generateCode();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Unique customer code generated successfully',
            'data'    => ['osa_code' => $code]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/agent_customers/export",
     *     summary="Export customer data in CSV or Excel format",
     *     tags={"AgentCustomer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Customer data exported successfully"),
     *     @OA\Response(response=404, description="No data available for export")
     * )
     */
    // public function export()
    // {
    //     ini_set('memory_limit', '-1');
    //     set_time_limit(0);
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));
    //     $filename = 'Agent_customers_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     $query = \DB::table('agent_customers')
    //         ->leftJoin('tbl_route', 'tbl_route.id', '=', 'agent_customers.route_id')
    //         ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'agent_customers.warehouse')
    //         ->leftJoin('customer_types', 'customer_types.id', '=', 'agent_customers.customer_type')
    //         ->leftJoin('outlet_channel', 'outlet_channel.id', '=', 'agent_customers.outlet_channel_id')
    //         ->leftJoin('customer_categories', 'customer_categories.id', '=', 'agent_customers.category_id')
    //         ->leftJoin('customer_sub_categories', 'customer_sub_categories.id', '=', 'agent_customers.subcategory_id')
    //         ->select(
    //             'agent_customers.osa_code',
    //             'agent_customers.name',
    //             'agent_customers.owner_name',
    //             'customer_types.name as customer_type',
    //             'tbl_route.route_name',
    //             'tbl_warehouse.warehouse_name',
    //             'outlet_channel.outlet_channel as outlet_channel',
    //             'customer_categories.customer_category_name as category',
    //             'customer_sub_categories.customer_sub_category_name as subcategory',
    //             'agent_customers.contact_no',
    //             'agent_customers.contact_no2',
    //             'agent_customers.whatsapp_no',
    //             'agent_customers.street',
    //             'agent_customers.town',
    //             'agent_customers.landmark',
    //             'agent_customers.district',
    //             'agent_customers.payment_type',
    //             'agent_customers.creditday',
    //             'agent_customers.vat_no',
    //             'agent_customers.credit_limit',
    //             'agent_customers.longitude',
    //             'agent_customers.latitude',
    //             'agent_customers.status'
    //         );

    //     if (!empty($filters)) {
    //         if (!empty($filters['status'])) {
    //             $query->where('agent_customers.status', $filters['status']);
    //         }
    //         if (!empty($filters['route_id'])) {
    //             $query->where('agent_customers.route_id', $filters['route_id']);
    //         }
    //         if (!empty($filters['search'])) {
    //             $query->where('agent_customers.name', 'like', '%' . $filters['search'] . '%');
    //         }
    //     }

    //     $data = $query->get();

    //     if ($data->isEmpty()) {
    //         return response()->json(['message' => 'No data available for export'], 404);
    //     }

    //     $export = new \App\Exports\AgentCustomerExport($data);
    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    //     $success = \Maatwebsite\Excel\Facades\Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );

    //     if (!$success) {
    //         throw new \Exception(strtoupper($format) . ' export failed.');
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;
    //     return response()->json(['url' => $fullUrl], 200);
    // }
    public function export()
    {

        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $filters = request()->input('filter', []);
        $columns = request()->input('columns', []);
        $search  = request()->input('search');
        $format  = strtolower(request()->input('format', 'csv'));
        $format  = in_array($format, ['csv', 'xlsx']) ? $format : 'csv';

        $filename = 'Agent_customers_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        $query = \DB::table('agent_customers')
            ->leftJoin('tbl_route', 'tbl_route.id', '=', 'agent_customers.route_id')
            ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'agent_customers.warehouse')
            ->leftJoin('customer_types', 'customer_types.id', '=', 'agent_customers.customer_type')
            ->leftJoin('outlet_channel', 'outlet_channel.id', '=', 'agent_customers.outlet_channel_id')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'agent_customers.category_id')
            ->leftJoin('customer_sub_categories', 'customer_sub_categories.id', '=', 'agent_customers.subcategory_id');

            $query = DataAccessHelper::filterAgentCustomers($query, Auth::user());

            $query->select(
                'agent_customers.osa_code',
                'agent_customers.name',
                'agent_customers.owner_name',
                'customer_types.name as customer_type',
                //'tbl_route.route_name',
                //'tbl_warehouse.warehouse_name',
                'tbl_route.route_code',
                'tbl_route.route_name',
                'tbl_warehouse.warehouse_code',
                'tbl_warehouse.warehouse_name',
                'outlet_channel.outlet_channel',
                'customer_categories.customer_category_name as category',
                'customer_sub_categories.customer_sub_category_name as subcategory',
                'agent_customers.contact_no',
                'agent_customers.contact_no2',
                'agent_customers.whatsapp_no',
                'agent_customers.street',
                'agent_customers.town',
                'agent_customers.landmark',
                'agent_customers.district',
                'agent_customers.payment_type',
                'agent_customers.creditday',
                'agent_customers.vat_no',
                'agent_customers.credit_limit',
                'agent_customers.longitude',
                'agent_customers.latitude',
                'agent_customers.status'
            );

        if (!empty($filters['status'])) {
            $query->where('agent_customers.status', $filters['status']);
        }

        if (!empty($filters['route_id'])) {
            $query->where('agent_customers.route_id', $filters['route_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->where('agent_customers.warehouse', $filters['warehouse_id']);
        }
        if (!empty($filters['customer_type'])) {
            $query->where('agent_customers.customer_type', $filters['customer_type']);
        }
        if (!empty($filters['channel_id'])) {
            $query->where('agent_customers.outlet_channel_id', $filters['channel_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('agent_customers.category_id', $filters['category_id']);
        }
        if (!empty($search)) {
            $like = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(agent_customers.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(agent_customers.osa_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(tbl_route.route_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(tbl_warehouse.warehouse_name) LIKE ?', [$like]);
            });
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data available for export'], 404);
        }

        $export = new \App\Exports\AgentCustomerExport($data, $columns);

        $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

        \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        return response()->json([
            'status' => 'success',
            'url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/master/agent_customers/bulk-update-status",
     *     summary="Bulk update status for agent customers",
     *     tags={"AgentCustomer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids","status"},
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1,2,3}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=0
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk status update successful"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:agent_customers,id',
            'status' => 'required',
        ]);
        $count = AgentCustomer::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);
        return response()->json([
            'status'        => 'success',
            'code'          => 200,
            'message'       => "Updated status for {$count} customers successfully",
            'updated_count' => $count,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/master/agent_customers/global_search",
     *     summary="Search agent customers globally across multiple fields and relations",
     *     tags={"AgentCustomer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword to match across multiple fields like name, contact_no, route, region, warehouse, etc."
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
     *                 @OA\Items(ref="#/components/schemas/AgentCustomer")
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
    public function global_search_agent_customer(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $keyword = $request->get('query');
            $warehouseId = $request->get('warehouse_id');

            $customers = $this->service->globalSearchAgentCustomer($perPage, $keyword, $warehouseId);

            return response()->json([
                'status'      => 'success',
                'code'        => 200,
                'message'     => 'Customers fetched successfully',
                'data'        => AgentCustomerResource::collection($customers->items()),
                'pagination'  => [
                    'page'         => $customers->currentPage(),
                    'limit'        => $customers->perPage(),
                    'totalPages'   => $customers->lastPage(),
                    'totalRecords' => $customers->total(),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAgentCustomersByWarehouse($warehouseId)
    {
        $customers = AgentCustomer::query()
            ->where('warehouse', $warehouseId)
            ->select([
                'id',
                'osa_code',
                'warehouse'
            ])
            ->orderBy('osa_code')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Agent customers fetched successfully.',
            'data' => $customers
        ]);
    }

    // public function getAgentCustomersByRoute(Request $request, $routeIds)
    // {
    //     $perPage = $request->per_page ?? 50;

    //     $routeIds = is_array($routeIds) ? $routeIds : explode(',', $routeIds);

    //     $query = AgentCustomer::query()->whereIn('route_id', $routeIds);
    //     if ($search = $request->get('search')) {
    //         if (is_numeric($search)) {
    //             $query->where(function($query) use ($search) {
    //                 $query->where('id', $search) 
    //                       ->orWhere('osa_code', 'like', '%' . $search . '%')
    //                       ->orWhere('name', 'like', '%' . $search . '%');
    //             });
    //         } else {
    //             $query->where(function($query) use ($search) {
    //                 $query->where('name', 'like', '%' . $search . '%')
    //                       ->orWhere('osa_code', 'like', '%' . $search . '%');
    //             });
    //         }
    //     }
    //     $customers = $query->select([
    //                 'id',
    //                 'name',
    //                 'osa_code',
    //                 'route_id'
    //             ])
    //             ->orderBy('osa_code')
    //             ->paginate($perPage);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Agent customers fetched successfully.',
    //         'data' => $customers->items(),
    //         'pagination' => [
    //             'total' => $customers->total(),
    //             'per_page' => $customers->perPage(),
    //             'current_page' => $customers->currentPage(),
    //             'last_page' => $customers->lastPage(),
    //         ]
    //     ]);
    // }

    public function getAgentCustomersByRoute(Request $request, $routeIds)
    {
        $perPage  = $request->per_page ?? 50;
        $dropdown = $request->boolean('dropdown', false);
        $status   = $request->get('status'); // ✅ READ STATUS

        // ✅ Allow comma-separated route IDs
        $routeIds = is_array($routeIds) ? $routeIds : explode(',', $routeIds);

        $query = AgentCustomer::query()
            ->whereIn('route_id', $routeIds);

        /**
         * 🔹 STATUS FILTER (DEFAULT = 1)
         */
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->where('status', 1);
        }

        /**
         * 🔹 SEARCH (UNCHANGED)
         */
        if ($search = $request->get('search')) {
            if (is_numeric($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('osa_code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('osa_code', 'like', '%' . $search . '%');
                });
            }
        }

        /**
         * 🔹 DROPDOWN MODE
         */
        if ($dropdown) {
            $customers = $query
                ->select([
                    'id',
                    'name',
                    'osa_code',
                    'status'
                ])
                ->orderBy('name')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Agent customers fetched successfully.',
                'data'    => $customers
            ]);
        }

        /**
         * 🔹 NORMAL PAGINATED MODE
         */
        $customers = $query
            ->select([
                'id',
                'name',
                'osa_code',
                'route_id'
            ])
            ->orderBy('osa_code')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Agent customers fetched successfully.',
            'data' => $customers->items(),
            'pagination' => [
                'total'        => $customers->total(),
                'per_page'     => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
            ]
        ]);
    }




    // public function global_search_agent_customer(Request $request)
    // {
    //     try {
    //         $perPage = $request->get('per_page', 10);
    //         $keyword = $request->get('query');
    //         $warehouseId = $request->get('warehouse_id');

    //         $agentCustomers = $this->service->globalSearchAgentCustomer($perPage, $keyword, $warehouseId);

    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 200,
    //             'message' => 'Search results',
    //             'data' => $agentCustomers->items(),
    //             'pagination' => [
    //                 'current_page' => $agentCustomers->currentPage(),
    //                 'last_page'    => $agentCustomers->lastPage(),
    //                 'per_page'     => $agentCustomers->perPage(),
    //                 'total'        => $agentCustomers->total(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 500,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function global_search_agent_customer(Request $request)
    // {
    //         $perPage = $request->get('per_page', 10);
    //         $keyword = $request->get('query');
    //         $agentCustomers = $this->service->globalSearchAgentCustomer($perPage, $keyword);
    //         // dd($agentCustomers);
    //         return response()->json([
    //             'status'      => 'success',
    //             'code'        => 200,
    //             'message'     => 'Customers fetched successfully',
    //             'data'        => AgentCustomerResource::collection($agentCustomers->items()),
    //             'pagination'  => [
    //                 'page'         => $agentCustomers->currentPage(),
    //                 'limit'        => $agentCustomers->perPage(),
    //                 'totalPages'   => $agentCustomers->lastPage(),
    //                 'totalRecords' => $agentCustomers->total(),
    //             ]
    //         ]);
    //         // return $this->success(
    //         //     $agentCustomers->items(),
    //         //     'Search results',
    //         //     200,
    //         //     [
    //         //         'pagination' => [
    //         //             'current_page' => $agentCustomers->currentPage(),
    //         //             'last_page'    => $agentCustomers->lastPage(),
    //         //             'per_page'     => $agentCustomers->perPage(),
    //         //             'total'        => $agentCustomers->total(),
    //         //         ]
    //         //     ]
    //         // );

    // }
}
