<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\BulkUploadRequest;
use App\Http\Requests\V1\MasterRequests\Web\SalesmanRequest;
use App\Http\Resources\V1\Master\Web\SalesmanResource;
use App\Services\V1\MasterServices\Web\SalesmanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponse;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\DB;

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
    use ApiResponse;

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
        $perPage = (int) $request->get('limit', 50);
        $filters = $request->except(['limit', 'page', 'dropdown']);
        $salesmen = $this->service->all($perPage, $filters, $isDropdown);
        if ($isDropdown) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Active Salesmen dropdown fetched successfully',
                'data' => $salesmen,
            ]);
        }
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Salesmen fetched successfully',
            'data' => SalesmanResource::collection($salesmen),
            'pagination' => [
                'page' => $salesmen->currentPage(),
                'limit' => $salesmen->perPage(),
                'totalPages' => $salesmen->lastPage(),
                'totalRecords' => $salesmen->total(),
            ],
        ]);
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
    $validated = $request->validated();
    $salesman = $this->service->create($validated);

    LogHelper::store(
        'master',               
        'salesman',           
        'add',               
        null,                   
        $salesman->toArray(),  
        auth()->id()             
    );

    return response()->json([
        'status' => 'success',
        'code'   => 200,
        'data'   => new SalesmanResource($salesman)
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
            'code'   => 200,
            'data'   => new SalesmanResource($salesman)
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

//   public function update(Request $request, string $uuid): JsonResponse
//     {
//         try {
//             // ✅ Inline validation (only for provided fields)
//             $validated = $request->validate([
//                 'osa_code' => [
//                     'sometimes',
//                     'string',
//                     'max:50',
//                     Rule::unique('salesman', 'osa_code')->ignore($uuid, 'uuid'),
//                 ],
//                 'name'            => 'sometimes|string|max:50',
//                 'type'            => 'sometimes|exists:salesman_types,id',
//                 'designation'     => 'sometimes|string|max:150',
//                 'route_id'        => 'sometimes|integer|exists:tbl_route,id',
//                 'username'        => [
//                     'sometimes',
//                     'string',
//                     'max:55',
//                     Rule::unique('salesman', 'username')->ignore($uuid, 'uuid'),
//                 ],
//                 // 'password'        => 'sometimes|string|max:150',
//                 'contact_no'      => 'sometimes|string|max:20',
//                 'warehouse_id'    => 'sometimes|string',
//                 'email'           => 'sometimes|email|max:100',
//                 'status'          => 'sometimes|integer|in:0,1',
//                 'forceful_login'  => 'sometimes|integer|in:0,1',
//                 'is_block'        => 'sometimes|integer|in:0,1',
//                 'is_block_reason' => 'sometimes|string|max:250|nullable',
//                 'block_date_from' => 'sometimes|date|nullable',
//                 'block_date_to'   => 'sometimes|date|after_or_equal:block_date_from|nullable',
//             ]);
//             $dataToUpdate = collect($validated)
//                 ->filter(fn($value, $key) => $request->has($key))
//                 ->toArray();
//             if (empty($dataToUpdate)) {
//                 return response()->json([
//                     'status'  => 'error',
//                     'code'    => 400,
//                     'message' => 'No valid fields provided for update.'
//                 ], 400);
//             }
//             $updatedSalesman = $this->service->updateByUuid($uuid, $dataToUpdate);

//             return response()->json([
//                 'status'  => 'success',
//                 'code'    => 200,
//                 'message' => 'Salesman updated successfully.',
//                 'data'    => new SalesmanResource($updatedSalesman),
//             ]);
//         } catch (Exception $e) {
//             return response()->json([
//                 'status'  => 'error',
//                 'code'    => 500,
//                 'message' => $e->getMessage(),
//             ], 500);
//         }
//     }

public function update(Request $request, string $uuid)
{
    try {
        $validated = $request->validate([
            'osa_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('salesman', 'osa_code')->ignore($uuid, 'uuid'),
            ],
            'name'        => 'sometimes|string|max:50',
            'type'        => 'sometimes|exists:salesman_types,id',
            'designation' => 'sometimes|string|max:150',
            'route_id'    => 'nullable|integer',
            'password'    => 'nullable|string|max:150',
            'contact_no'  => 'sometimes|string|max:20',
            'warehouse_id' => [
                'sometimes',
                function ($attribute, $value, $fail) {
                    if (!is_array($value) && !is_string($value)) { 
                        $fail($attribute.' must be a string or an array.');
                    }
                }
            ],
            'warehouse_id.*' => 'exists:tbl_warehouse,id',

            'email'        => 'nullable|email|max:100',
            'status'        => 'sometimes|integer|in:0,1',
            'forceful_login'=> 'sometimes|integer|in:0,1',
            'is_block'      => 'sometimes|integer|in:0,1',
            'block_date_from' => [
                'sometimes',
                'nullable',
                'date',
                Rule::requiredIf(fn() => $request->input('is_block') == 1),
            ],
            'block_date_to' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:block_date_from',
                Rule::requiredIf(fn() => $request->input('is_block') == 1),
            ],
            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:250',
                Rule::requiredIf(fn() => $request->input('invoice_block') == 1),
            ],
            'cashier_description_block' => 'sometimes|integer|in:0,1',
            'invoice_block'             => 'sometimes|integer|in:0,1',
        ]);

        $dataToUpdate = collect($validated)
            ->filter(fn($value, $key) => $request->has($key))
            ->toArray();

        if (empty($dataToUpdate)) {
            return $this->fail("No valid fields provided for update.", 400);
        }

        $oldSalesman = $this->service->findByUuid($uuid);
        $previousData = $oldSalesman ? $oldSalesman->toArray() : null;
        $updated = $this->service->updateByUuid($uuid, $dataToUpdate);
        $currentData = $updated ? $updated->toArray() : null;
        LogHelper::store(
            'master',        
            'salesman',   
            'update',         
            $previousData,     
            $currentData,    
            auth()->id()        
        );

        return $this->success(
            new SalesmanResource($updated),
            "Salesman updated successfully",
            200
        );

    } catch (Exception $e) {
        return $this->fail($e->getMessage(), 500);
    }
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
    $format  = $request->get('format', 'xlsx');
    $fromDate = $request->get('from_date');
    $toDate   = $request->get('to_date');
    $search   = $request->get('search');
    $filters  = $request->get('filter', []);
    $columns  = $request->get('columns', []);
    return $this->service->export(
        $format,
        $fromDate,
        $toDate,
        $search,
        $filters,
        $columns
    );
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

    /**
     * @OA\Get(
     *     path="/api/master/salesmen/salesmanRoute/{uuid}",
     *     tags={"Salesman"},
     *     summary="Get route assigned to a specific salesman using UUID",
     *     description="Fetch route details assigned to a salesman using their UUID.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the salesman",
     *         @OA\Schema(type="string", format="uuid", example="b6a47f5e-32b7-4f7a-8d4c-1cbf6a5dbe9c")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Salesman route fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="salesman", type="object",
     *                     @OA\Property(property="uuid", type="string", example="b6a47f5e-32b7-4f7a-8d4c-1cbf6a5dbe9c"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="osa_code", type="string", example="OSA123")
     *                 ),
     *                 @OA\Property(property="route", type="object",
     *                     @OA\Property(property="id", type="integer", example=12),
     *                     @OA\Property(property="code", type="string", example="RT001"),
     *                     @OA\Property(property="name", type="string", example="Route A")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Salesman not found or no route assigned",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Salesman not found or route not assigned")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function getSalesmanRoute(string $uuid)
    {
        $result = $this->service->getSalesmanRouteByUuid($uuid);

        return response()->json([
            'status' => $result['status'],
            'code' => $result['code'] ?? null,
            'data' => $result['data'] ?? null,
        ], $result['code']);
    }


    /**
     * @OA\Get(
     *     path="/api/master/salesmen/routeSalesman/{id}",
     *     tags={"Salesman"},
     *     summary="Get all salesmen by route ID or UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Route UUID or ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of salesmen for this route"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found or no salesmen assigned"
     *     )
     * )
     */
    public function getSalesmenByRoute(string $id)
    {
        $result = $this->service->getSalesmenByRouteUuid($id);

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'code' => $result['code'] ?? null,
            'data' => $result['data'] ?? null,
        ], $result['code']);
    }

    /**
 * @OA\Get(
 *     path="/api/master/salesmen/global_search",
 *     summary="Search salesmen globally across multiple fields",
 *     tags={"Salesman"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="query",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="string"),
 *         description="Search keyword to match across fields like name, osa_code, username, contact_no, email, warehouse, route, etc."
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
 *                 @OA\Items(ref="#/components/schemas/Salesman")
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=1),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
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
public function global_search(Request $request): JsonResponse
{
    try {
        $perPage = (int) ($request->get('per_page', $request->get('perPage', 50)));
        $keyword = trim($request->get('query', ''));
        if (empty($keyword)) {
            $filters = $request->except(['per_page', 'perPage', 'page', 'query']);
            $salesmen = $this->service->all($perPage, $filters, false);
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Salesmen fetched successfully (no search query provided)',
                'data' => SalesmanResource::collection($salesmen),
                'pagination' => [
                    'page' => $salesmen->currentPage(),
                    'limit' => $salesmen->perPage(),
                    'totalPages' => $salesmen->lastPage(),
                    'totalRecords' => $salesmen->total(),
                ],
            ]);
        }
        $salesmen = $this->service->globalSearch($perPage, $keyword);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Search results',
            'data' => SalesmanResource::collection($salesmen->items()),
            'pagination' => [
                'page' => $salesmen->currentPage(),
                'limit' => $salesmen->perPage(),
                'totalPages' => $salesmen->lastPage(),
                'totalRecords' => $salesmen->total(),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => $e->getMessage(),
        ], 500);
    }
}

// public function salespersalesman(Request $request, string $uuid): JsonResponse
//     {
//         try {
//             $perPage = (int) $request->get('limit', 50);
//             $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
//             $salesList = $this->service->salespersalesman($uuid, $perPage, $isDropdown);
//             if ($isDropdown) {
//                 return response()->json([
//                     'status' => 'success',
//                     'code' => 200,
//                     'message' => 'Sales dropdown fetched successfully',
//                     'data' => $salesList,
//                 ]);
//             }
//             return response()->json([
//                 'status' => 'success',
//                 'code' => 200,
//                 'message' => 'Sales details fetched successfully',
//                 'data' => $salesList->items(),
//                 'pagination' => [
//                     'page' => $salesList->currentPage(),
//                     'limit' => $salesList->perPage(),
//                     'totalPages' => $salesList->lastPage(),
//                     'totalRecords' => $salesList->total(),
//                 ],
//             ]);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'status' => 'error',
//                 'code' => 500,
//                 'message' => $e->getMessage(),
//             ], 500);
//         }
//     }
// public function salespersalesman(Request $request, string $uuid): JsonResponse
// {
//     try {
//         $perPage = (int) $request->get('limit', 50);
//         $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
//         $from = $request->get('from');
//         $to = $request->get('to');
//         $salesList = $this->service->salespersalesman($uuid, $perPage, $isDropdown, $from, $to);
//         if ($isDropdown) {
//             return response()->json([
//                 'status' => 'success',
//                 'code' => 200,
//                 'message' => 'Sales dropdown fetched successfully',
//                 'data' => $salesList,
//             ]);
//         }
//         return response()->json([
//             'status' => 'success',
//             'code' => 200,
//             'message' => 'Sales details fetched successfully',
//             'data' => $salesList->items(),
//             // 'data'    =>$salesList,
//             'pagination' => [
//                 'page' => $salesList->currentPage(),
//                 'limit' => $salesList->perPage(),
//                 'totalPages' => $salesList->lastPage(),
//                 'totalRecords' => $salesList->total(),
//             ],
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'code' => 500,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }
public function salespersalesman(Request $request, string $uuid): JsonResponse
{
    try {
        $perPage = (int) $request->get('limit', 50);
        $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $salesList = $this->service->salespersalesman(
            $uuid,
            $perPage,
            $isDropdown,
            $fromDate,
            $toDate
        );

        if ($isDropdown) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Sales dropdown fetched successfully',
                'data' => $salesList,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Sales details fetched successfully',
            'data' => $salesList->items(),
            'pagination' => [
                'page' => $salesList->currentPage(),
                'limit' => $salesList->perPage(),
                'totalPages' => $salesList->lastPage(),
                'totalRecords' => $salesList->total(),
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => $e->getMessage(),
        ], 500);
    }
}

// public function exportSalesmanInvoices(Request $request, string $uuid)
// {
//     try {
//         $format = $request->get('format', 'xlsx');
//         if (!in_array($format, ['csv', 'xlsx'])) {
//             return $this->fail('Invalid export format. Allowed formats: csv, xlsx', 422);
//         }
//         return $this->service->exportInvoicesBySalesman($uuid, $format);
//     } catch (\Exception $e) {
//         return $this->fail('Export failed: ' . $e->getMessage(), 500);
//     }
// }


 public function exportSalesmanInvoices(Request $request, string $uuid)
    {
        try {
            $format = strtolower($request->get('format', 'csv'));
            
            if (!in_array($format, ['csv', 'xlsx'])) {
                return $this->fail('Invalid export format. Allowed formats: csv, xlsx', 422);
            }
            $result = $this->service->exportInvoicesBySalesman($uuid, $format);
            return $this->success($result, 'Export generated successfully');
        } catch (\Exception $e) {
            return $this->fail('Export failed: ' . $e->getMessage(), 500);
        }
    }


// public function salesmanOrder(Request $request, string $uuid)
// {
//     try {
//         $perPage = (int) $request->get('limit', 50);
//         $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
//         $orderList = $this->service->salesmanOrder($uuid, $perPage, $isDropdown);

//         if ($isDropdown) {
//             return response()->json([
//                 'status' => 'success',
//                 'code' => 200,
//                 'message' => 'Order dropdown fetched successfully',
//                 'data' => $orderList,
//             ]);
//         }
//         return response()->json([
//             'status' => 'success',
//             'code' => 200,
//             'message' => 'Salesman orders fetched successfully',
//             'data' => $orderList->items(),
//             'pagination' => [
//                 'page' => $orderList->currentPage(),
//                 'limit' => $orderList->perPage(),
//                 'totalPages' => $orderList->lastPage(),
//                 'totalRecords' => $orderList->total(),
//             ],
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'code' => 500,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }
public function salesmanOrder(Request $request, string $uuid)
{
    try {
        $perPage = (int) $request->get('limit', 50);
        $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $from = $request->get('from');
        $to = $request->get('to');
        $orderList = $this->service->salesmanOrder($uuid, $perPage, $isDropdown, $from, $to);
        if ($isDropdown) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Order dropdown fetched successfully',
                'data' => $orderList,
            ]);
        }
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Salesman orders fetched successfully',
            'data' => $orderList->items(),
            'pagination' => [
                'page' => $orderList->currentPage(),
                'limit' => $orderList->perPage(),
                'totalPages' => $orderList->lastPage(),
                'totalRecords' => $orderList->total(),
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function report(Request $request)
{
    $request->validate([
        'salesman_uuid' => 'required|string',
        'from_date'   => 'nullable|date',
        'to_date'     => 'nullable|date|after_or_equal:from_date',
        'limit'       => 'nullable|integer|min:1'
    ]);

    $salesmanUuid = $request->salesman_uuid;
    $fromDate   = $request->from_date;
    $toDate     = $request->to_date;
    $perPage    = $request->input('limit', 10);

    $query = DB::table('salesman_attendance as sa')
        ->leftJoin('tbl_route as r', 'r.id', '=', 'sa.route_id')
        ->leftJoin('tbl_warehouse as w', 'w.id', '=', 'sa.warehouse_id')
        ->leftJoin('salesman as s', 's.id', '=', 'sa.salesman_id')
        ->leftJoin('salesman_types as st', 'st.id', '=', 's.type')
        ->leftJoin('project_list as pl', function ($join) {
            $join->on('pl.id', '=', DB::raw('CAST(s.sub_type AS INTEGER)'));
        })

        ->where('s.uuid', $salesmanUuid)
        ->orderBy('sa.attendance_date', 'asc')
        ->select([
            'sa.id',
            'sa.attendance_date', 
             DB::raw("
                CASE 
                    WHEN s.type = 6 THEN pl.name
                    ELSE st.salesman_type_name
                END AS salesman_type
            "),
              DB::raw("
                COALESCE(NULLIF(s.sap_id, ''), s.osa_code) AS salesman_code
            "),
            's.name as salesman_name',
            'r.route_name',
            'r.route_code',
            'w.warehouse_name',
            'w.warehouse_code',
            'w.latitude as warehouse_latitude',
            'w.longitude as warehouse_longitude',
            'sa.time_in',
            'sa.in_img',
            'sa.latitude_in as attendance_latitude_in',
            'sa.longitude_in as attendance_longitude_in',
            'sa.time_out',
            'sa.out_img',
            'sa.latitude_out as attendance_latitude_out',
            'sa.longitude_out as attendance_longitude_out',
        ]);

    if ($fromDate && $toDate) {
        $query->whereBetween('sa.attendance_date', [$fromDate, $toDate]);
    }

    $data = $query->paginate($perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'Salesman attendance report fetched successfully',
        'data'       => $data->items(),
        'pagination' => [
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'per_page'     => $data->perPage(),
            'total'        => $data->total(),
        ],
    ]);
}
/**
 * @OA\Get(
 *     path="/api/master/salesmen/orders-export/{salesman_uuid}",
 *     tags={"Salesman"},
 *     security={{"bearerAuth":{}}},
 *     summary="Export salesman orders in Excel",
 *     description="Exports order header and order details in Excel format based on salesman UUID. Excel file contains expandable/collapsible order details.",
 *
 *     @OA\Parameter(
 *         name="salesman_uuid",
 *         in="path", 
 *         required=true,
 *         description="UUID of the salesman",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9f1b2c3d-1234-4567-890a-bcdef1234567"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Excel exported successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Excel exported successfully"),
 *             @OA\Property(
 *                 property="file_path",
 *                 type="string",
 *                 example="https://yourdomain.com/storage/salesman_order/salesman_orders_X8Y2.xlsx"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Salesman not found"
 *     )
 * )
 */
public function export(string $salesman_uuid)
{
    validator(
        ['salesman_uuid' => $salesman_uuid],
        ['salesman_uuid' => 'required|uuid']
    )->validate();

    $filePath = app(SalesmanService::class)
        ->exportBySalesmanUuid($salesman_uuid);

    return response()->json([
        'status' => true,
        'message' => 'Excel exported successfully',
        'file_path' => $filePath
    ]);
}
/**
 * @OA\Get(
 *     path="/api/master/salesmen/po-export/{salesman_uuid}",
 *     tags={"Salesman"},
 *     security={{"bearerAuth":{}}},
 *     summary="Export salesman orders in Excel",
 *     description="Exports order header and order details in Excel format based on salesman UUID. Excel file contains expandable/collapsible order details.",
 *
 *     @OA\Parameter(
 *         name="salesman_uuid",
 *         in="path", 
 *         required=true,
 *         description="UUID of the salesman",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9f1b2c3d-1234-4567-890a-bcdef1234567"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Excel exported successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Excel exported successfully"),
 *             @OA\Property(
 *                 property="file_path",
 *                 type="string",
 *                 example="https://yourdomain.com/storage/salesman_order/salesman_orders_X8Y2.xlsx"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="Salesman not found"
 *     )
 * )
 */
public function po_export(string $salesman_uuid)
{
    validator(
        ['salesman_uuid' => $salesman_uuid],
        ['salesman_uuid' => 'required|uuid']
    )->validate();

    $filePath = app(SalesmanService::class)
        ->export_po($salesman_uuid);

    return response()->json([
        'status' => true,
        'message' => 'Excel exported successfully',
        'file_path' => $filePath
    ]);
}
}
