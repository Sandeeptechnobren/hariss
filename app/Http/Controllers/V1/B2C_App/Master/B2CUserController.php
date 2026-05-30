<?php

namespace App\Http\Controllers\V1\B2C_App\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\AgentCustomerRequest;
use App\Http\Resources\V1\Master\Web\AgentCustomerDropdownResource;
use App\Http\Resources\V1\Master\Web\AgentCustomerResource;
use App\Services\V1\B2C_App\Master\B2CUserService;
use Illuminate\Http\JsonResponse;
use App\Models\AgentCustomer;
use Illuminate\Http\Request;
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
class B2CUserController extends Controller
{
    protected B2CUserService $service;

    public function __construct(B2CUserService $service)
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
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:200',
        ]);
        // dd($validated);
        $oldCustomer = $this->service->findByUuid($uuid);

        if (!$oldCustomer) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Customer not found',
            ], 404);
        }

        $previousData = $oldCustomer->toArray();

        $updated = $this->service->updateByUuid($uuid, $validated);

        $currentData = $updated->toArray();

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
