<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Exports\AgentCustomerExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\AgentCustomerRequest;
use App\Http\Resources\V1\Master\Web\AgentCustomerResource;
use App\Services\V1\MasterServices\Web\AgentCustomerService;
use Illuminate\Http\JsonResponse;
use App\Models\AgentCustomer;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'osa_code',
            'name',
            'owner_name',
            'business_name',
            'route_id',
            'outlet_channel_id',
            'category_id',
            'subcategory_id',
            'region_id',
            'area_id',
            'status',
            'warehouse_id'
        ]);
        $perPage = $request->get('limit', 10);
        $customers = $this->service->getAll($perPage, $filters);
        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Customers fetched successfully',
            'data'       => AgentCustomerResource::collection($customers->items()),
            'pagination' => [
                'page'         => $customers->currentPage(),
                'limit'        => $customers->perPage(),
                'totalPages'   => $customers->lastPage(),
                'totalRecords' => $customers->total(),
            ]
        ]);
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
        $customer = $this->service->create($request->validated());

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
            'payment_type'      => 'sometimes|in:cash,credit,billTobill',
            'is_cash'           => 'sometimes|in:0,1',
            'vat_no'            => 'sometimes|string',
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
        $updated = $this->service->updateByUuid($uuid, $validated);
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
    public function export()
    {
        $filters = request()->input('filters', []);
        $format = strtolower(request()->input('format', 'csv'));
        $filename = 'Agent_customers_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        $query = \DB::table('agent_customers')
            ->leftJoin('tbl_route', 'tbl_route.id', '=', 'agent_customers.route_id')
            ->leftJoin('tbl_warehouse', 'tbl_warehouse.id', '=', 'agent_customers.warehouse')
            ->leftJoin('customer_types', 'customer_types.id', '=', 'agent_customers.customer_type')
            ->leftJoin('outlet_channel', 'outlet_channel.id', '=', 'agent_customers.outlet_channel_id')
            ->leftJoin('customer_categories', 'customer_categories.id', '=', 'agent_customers.category_id')
            ->leftJoin('customer_sub_categories', 'customer_sub_categories.id', '=', 'agent_customers.subcategory_id')
            ->select(
                'agent_customers.osa_code',
                'agent_customers.name',
                'agent_customers.owner_name',
                'customer_types.name as customer_type',
                'tbl_route.route_name',
                'tbl_warehouse.warehouse_name',
                'outlet_channel.outlet_channel as outlet_channel',
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

        if (!empty($filters)) {
            if (!empty($filters['status'])) {
                $query->where('agent_customers.status', $filters['status']);
            }
            if (!empty($filters['route_id'])) {
                $query->where('agent_customers.route_id', $filters['route_id']);
            }
            if (!empty($filters['search'])) {
                $query->where('agent_customers.name', 'like', '%' . $filters['search'] . '%');
            }
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'No data available for export'], 404);
        }

        $export = new \App\Exports\AgentCustomerExport($data);
        $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

        $success = \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        if (!$success) {
            throw new \Exception(strtoupper($format) . ' export failed.');
        }

        $downloadUrl = \Storage::disk('public')->url($filePath);
        return response()->json(['url' => $downloadUrl], 200);
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

}
