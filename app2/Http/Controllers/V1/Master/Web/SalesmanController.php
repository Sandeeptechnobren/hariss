<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\BulkUploadRequest;
use App\Http\Requests\V1\MasterRequests\Web\SalesmanRequest;
use App\Http\Resources\V1\Master\Web\SalesmanResource;
use App\Services\V1\MasterServices\Web\SalesmanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Salesman",
 *     type="object",
 *     required={"designation", "route_id", "username", "token_no", "warehouse_id", "status"},
 *     @OA\Property(property="name", type="string", example="John Doe", description="Full name of the salesman"),
 *     @OA\Property(property="type", type="integer", example=2, description="Type of salesman: 0=Project, 1=Harris Sales Executive, 2=Agent Sales Executive"),
 *     @OA\Property(property="sub_type", type="integer", example=0, description="Sub-type: 0=MIT, 1=Technician, etc."),
 *     @OA\Property(property="designation", type="string", example="Sales Executive", description="Job designation/title"),
 *     @OA\Property(property="security_code", type="string", example="ABC123SEC", description="Security code for authentication or tracking"),
 *     @OA\Property(property="device_no", type="string", example="DEV12345", description="Device number assigned to salesman"),
 *     @OA\Property(property="route_id", type="integer", example=1, description="Assigned route ID"),
 *     @OA\Property(property="block_date_from", type="string", format="date", example="2025-09-01", description="Start date for sales block"),
 *     @OA\Property(property="block_date_to", type="string", format="date", example="2025-09-30", description="End date for sales block"),
 *     @OA\Property(property="salesman_role", type="integer", example=1, description="Role ID defining permissions and hierarchy"),
 *     @OA\Property(property="username", type="string", example="johndoe", description="Login username"),
 *     @OA\Property(property="password", type="string", example="password123", description="Login password"),
 *     @OA\Property(property="contact_no", type="string", example="9876543210", description="Contact phone number"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1, description="Assigned warehouse ID"),
 *     @OA\Property(property="token_no", type="string", example="TOKEN12345", description="Unique token for authentication"),
 *     @OA\Property(property="sap_id", type="string", example="SAP12345", description="SAP integration ID"),
 *     @OA\Property(property="is_login", type="integer", example=0, description="Login status: 0=Logged out, 1=Logged in"),
 *     @OA\Property(property="status", type="integer", example=1, description="Salesman status: 0=Inactive, 1=Active")
 * )
 */
class SalesmanController extends Controller
{
    private SalesmanService $service;

    public function __construct(SalesmanService $service)
    {
        $this->service = $service;
    }
        /**
     * @OA\get(
     *     path="/api/master/salesmen/list",
     *     tags={"Salesman"},
     *     summary="Create a new salesman",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=201, description="Salesman list fetched successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */

public function index(Request $request): JsonResponse
{
    $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
    $perPage = $request->get('limit', 10);
    $filters = $request->except(['limit', 'page', 'dropdown']);

    $salesmen = $this->service->all($perPage, $filters, $isDropdown);
    if ($isDropdown) {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $salesmen,
        ]);
    } else {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => SalesmanResource::collection($salesmen),
            'pagination' => [
                'page' => $salesmen->currentPage(),
                'limit' => $salesmen->perPage(),
                'totalPages' => $salesmen->lastPage(),
                'totalRecords' => $salesmen->total(),
            ],
        ]);
    }
}




    /**
     * @OA\Post(
     *     path="/api/master/salesmen/add",
     *     tags={"Salesman"},
     *     summary="Create a new salesman",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Salesman")),
     *     @OA\Response(response=201, description="Salesman created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(SalesmanRequest $request): JsonResponse
    {   
        $salesman = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => new SalesmanResource($salesman)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/master/salesmen/{uuid}",
     *     tags={"Salesman"},
     *     summary="Get single salesman by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Salesman details", @OA\JsonContent(ref="#/components/schemas/Salesman")),
     *     @OA\Response(response=404, description="Salesman not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $salesman = $this->service->findByUuid($uuid);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => new SalesmanResource($salesman)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/salesmen/update/{uuid}",
     *     tags={"Salesman"},
     *     summary="Update an existing salesman by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Salesman")),
     *     @OA\Response(response=200, description="Salesman updated successfully"),
     *     @OA\Response(response=404, description="Salesman not found")
     * )
     */
    // public function update(SalesmanRequest $request, string $uuid): JsonResponse
    // {
    //     $updated = $this->service->updateByUuid($uuid, $request->validated());

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => new SalesmanResource($updated)
    //     ]);
    // }

public function update(Request $request, string $uuid): JsonResponse
{
    // Perform inline validation here
    $validated = $request->validate([
        'osa_code' => [
            'sometimes',
            'string',
            'max:50',
            Rule::unique('salesman', 'osa_code')->ignore($uuid, 'uuid'),
        ],
        'name'  => 'nullable|string|max:50',
        'type'  => 'sometimes|exists:salesman_types,id',
        'designation'    => 'sometimes|string|max:150',
        'route_id'       => 'sometimes|integer|exists:tbl_route,id',
        'username'       => [
            'sometimes',
            'string',
            'max:55',
            Rule::unique('salesman', 'username')->ignore($uuid, 'uuid'),
        ],
        'password'   => 'sometimes|string|max:150',
        'contact_no'     => 'nullable|string|max:20',
        'warehouse_id'   => 'sometimes|integer|exists:tbl_warehouse,id',
        'email'     => 'nullable|email|max:100',
        'status'         => 'sometimes|integer|in:0,1',
        'forceful_login'    => 'sometimes|integer|in:0,1',
        'is_block'          => 'sometimes|integer|in:0,1',
        'is_block_reason'   => 'nullable|string|max:250',
        'block_date_from'   => 'nullable|date',
        'block_date_to'     => 'nullable|date|after_or_equal:block_date_from',
    ]);

    // Now call your service
    $updated = $this->service->updateByUuid($uuid, $validated);

    return response()->json([
        'status' => 'success',
        'code' => 200,
        'data' => new SalesmanResource($updated)
    ]);
}

    /**
     * @OA\Delete(
     *     path="/api/master/salesmen/{uuid}",
     *     tags={"Salesman"},
     *     summary="Delete a salesman by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Salesman deleted successfully"),
     *     @OA\Response(response=404, description="Salesman not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->service->deleteByUuid($uuid);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Salesman deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/salesmen/generate-code",
     *     tags={"Salesman"},
     *     summary="Generate unique salesman code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Generated unique salesman code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="string", example="SA0001")
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        $code = $this->service->generateCode();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => ['salesman_code' => $code]
        ]);
    }
/**
 * @OA\Get(
 *     path="/api/master/salesmen/exportSalesmen",
 *     summary="Export Salesmen data",
 *     description="Exports salesmen data with optional filters for date range.",
 *     operationId="exportSalesmen",
 *     security={{"bearerAuth":{}}},
 *     tags={"Salesman"},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         description="The format of the export file",
 *         required=false,
 *         @OA\Schema(
 *             type="string",
 *             enum={"xlsx", "csv"},
 *             default="xlsx"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="from_date",
 *         in="query",
 *         description="Start date for filtering salesmen records",
 *         required=false,
 *         @OA\Schema(
 *             type="string",
 *             format="date",
 *             example="2023-01-01"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="to_date",
 *         in="query",
 *         description="End date for filtering salesmen records",
 *         required=false,
 *         @OA\Schema(
 *             type="string",
 *             format="date",
 *             example="2023-12-31"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Salesmen data exported successfully",
 *         @OA\MediaType(
 *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
 *             @OA\Schema(type="string", format="binary")
 *         ),
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request parameters",
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error",
 *     )
 * )
 */
public function exportSalesmen(Request $request)
    {
        $format = $request->get('format', 'xlsx');
        $fromDate = $request->get('from_date'); 
        $toDate = $request->get('to_date');     
        return $this->service->export($format, $fromDate, $toDate);
    }

/**
 * @OA\Post(
 *     path="/api/master/salesmen/update-status",
 *     summary="Update status for multiple salesmen",
 *     description="Updates the status of multiple salesmen by their IDs.",
 *     operationId="updateMultipleSalesmanStatus",
 *     tags={"Salesman"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"salesman_ids", "status"},
 *             @OA\Property(
 *                 property="salesman_ids",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example={1, 2, 3}
 *             ),
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 example=1
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Salesman statuses updated.",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Salesman statuses updated.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"salesman_ids.0": {"The selected salesman_ids.0 is invalid."}}
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

public function updateMultipleSalesmanStatus(Request $request)
{
    $request->validate([
        'salesman_ids' => 'required|array|min:1',
        'salesman_ids.*' => 'integer|exists:salesman,id',
        'status' => 'required|integer',
    ]);

    $salesmanIds = $request->input('salesman_ids');
    $status = $request->input('status');

    $result = $this->service->updateSalesmenStatus($salesmanIds, $status);

    if ($result) {
        return response()->json(['success' => true, 'message' => 'Salesman statuses updated.'], 200);
    } else {
        return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
    }
}
}