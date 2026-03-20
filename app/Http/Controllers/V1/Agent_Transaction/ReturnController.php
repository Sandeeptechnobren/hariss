<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreReturnRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateReturnRequest;
use App\Http\Resources\V1\Agent_Transaction\ReturnHeaderResource;
use App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderBasedReturn;
use App\Services\V1\Agent_Transaction\ReturnService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\ReturnType;
use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\Agent_Transaction\ResonType;
use App\Models\AgentCustomer;
use App\Exports\ReturnHeaderExport;
use App\Exports\ReturnHeaderDetailExport;
use App\Exports\ReturnAgentCustomerExport;
use App\Exports\ReturnCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;
use App\Helpers\ResponseHelper;
use App\Exports\ReturnWarehouseCollapseExport;

class ReturnController extends Controller
{
    protected ReturnService $service;

    public function __construct(ReturnService $service)
    {
        $this->service = $service;
    }


    /**
     * @OA\Post(
     *     path="/api/agent_transaction/returns/create",
     *     tags={"Agent Returns"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new return transaction",
     *     description="Creates a return header and its details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"currency","country_id","warehouse_id","route_id","customer_id","details"},
     *             @OA\Property(property="osa_code", type="string", example="RET-2025-0001"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="integer", nullable=true, example=12),
     *             @OA\Property(property="delivery_id", type="integer", nullable=true, example=7),
     *             @OA\Property(property="warehouse_id", type="integer", example=3),
     *             @OA\Property(property="route_id", type="integer", example=5),
     *             @OA\Property(property="customer_id", type="integer", example=22),
     *             @OA\Property(property="salesman_id", type="integer", nullable=true, example=8),
     *             @OA\Property(property="gross_total", type="number", format="float", example=450.00),
     *             @OA\Property(property="vat", type="number", format="float", example=22.50),
     *             @OA\Property(property="net_amount", type="number", format="float", example=427.50),
     *             @OA\Property(property="total", type="number", format="float", example=450.00),
     *             @OA\Property(property="discount", type="number", format="float", example=0),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 minItems=1,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom_id","item_price","item_quantity"},
     *                     @OA\Property(property="item_id", type="integer", example=101),
     *                     @OA\Property(property="uom_id", type="integer", example=1),
     *                     @OA\Property(property="discount_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="promotion_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="item_price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="item_quantity", type="number", format="float", example=2),
     *                     @OA\Property(property="vat", type="number", format="float", example=15.00),
     *                     @OA\Property(property="discount", type="number", format="float", example=0),
     *                     @OA\Property(property="gross_total", type="number", format="float", example=300.00),
     *                     @OA\Property(property="net_total", type="number", format="float", example=285.00),
     *                     @OA\Property(property="total", type="number", format="float", example=300.00),
     *                     @OA\Property(property="is_promotional", type="boolean", example=false),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Return transaction created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     * @OA\Property(property="data",type="object",@OA\Property(property="uuid", type="string"),@OA\Property(property="osa_code", type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to create return transaction")
     *         )
     *     )
     * )
     */

    public function store(StoreReturnRequest $request): JsonResponse
    {
        try {
            $return = $this->service->create($request->validated());
            if ($return) {
                LogHelper::store(
                    '13',
                    '80',
                    'add',
                    null,
                    $return->getAttributes(),
                    auth()->id()
                );
            }
            if (!$return) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create return transaction',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'data' => new ReturnHeaderResource($return),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create return transaction',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/agent_transaction/returns/list",
     *     tags={"Agent Returns"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list of return transactions",
     *     description="Fetch paginated list of return transactions or simple dropdown list when dropdown=true.",
     *     @OA\Response(
     *         response=200,
     *         description="Returns fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Returns fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="osa_code", type="string", example="RET-2025-0001"),
     *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="warehouse_name", type="string", example="Main Warehouse"),
     *                     @OA\Property(property="gross_total", type="number", format="float", example=450.00),
     *                     @OA\Property(property="net_amount", type="number", format="float", example=427.50),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", example="2025-11-08T10:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="totalPages", type="integer", example=10),
     *                 @OA\Property(property="totalRecords", type="integer", example=500)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error or failed to retrieve returns",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve returns"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[HY000]: General error ...")
     *         )
     *     )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters = $request->except(['limit', 'dropdown']);

            // dd($perPage);
            $returns = $this->service->getAll($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $returns,
                ]);
            }

            $pagination = [
                'page'          => $returns->currentPage(),
                'limit'         => $returns->perPage(),
                'totalPages'    => $returns->lastPage(),
                'totalRecords'  => $returns->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Returns fetched successfully',
                'data'    => ReturnHeaderResource::collection($returns),
                'meta'    => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve returns',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->all();

            $returns = $this->service->globalFilter($perPage, $filters);

            // ❌ dropdown removed
            // ✅ response unchanged
            return ResponseHelper::paginatedResponse(
                'Returns fetched successfully',
                ReturnHeaderResource::class,
                $returns
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // /**
    //  * @OA\Get(
    //  *     path="/api/agent_transaction/returns/show/{uuid}",
    //  *     tags={"Agent Returns"},
    //  *     summary="Get a single return transaction by UUID",
    //  *     description="Fetch detailed information for a specific return transaction including header and details.",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         description="UUID of the return transaction",
    //  *         required=true,
    //  *         @OA\Schema(type="string", example="a1f2e5f0-9abc-11ee-b9d1-0242ac120002")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return transaction retrieved successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="data", ref="#/components/schemas/ReturnHeaderResource")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return transaction not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return transaction not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error retrieving return transaction",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=500),
    //  *             @OA\Property(property="message", type="string", example="Failed to retrieve return transaction"),
    //  *             @OA\Property(property="error", type="string", example="Exception message")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function show(string $uuid): JsonResponse
    {
        try {
            $return = $this->service->getByUuid($uuid);

            if (!$return) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return transaction not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new ReturnHeaderResource($return),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return transaction',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/agent_transaction/returns/delete/{uuid}",
    //  *     tags={"Agent Returns"},
    //  *     summary="Delete a return transaction by UUID",
    //  *     description="Permanently deletes a specific return transaction and its associated details.",
    //  *     operationId="deleteReturnTransaction",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         description="UUID of the return transaction to delete",
    //  *         required=true,
    //  *         @OA\Schema(type="string", example="a1f2e5f0-9abc-11ee-b9d1-0242ac120002")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return transaction deleted successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="message", type="string", example="Return transaction deleted successfully")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return transaction not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return transaction not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Internal server error",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=500),
    //  *             @OA\Property(property="message", type="string", example="Failed to delete return transaction"),
    //  *             @OA\Property(property="error", type="string", example="Exception message")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $result = $this->service->delete($uuid);

            if (! $result) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return transaction not found'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Return transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to delete return transaction',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/agent_transaction/returns/updatestatus",
    //  *     summary="Update multiple return statuses",
    //  *     description="Updates the status of multiple return records based on their UUIDs.",
    //  *     tags={"Return"},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(
    //  *                 property="return_ids",
    //  *                 type="array",
    //  *                 @OA\Items(type="string", format="uuid"),
    //  *                 example={"a2d9e0e1-4b72-4f98-92a3-784ea931bcb9", "b4c2a7a3-6e52-4f8b-8c93-98afaa48df24"}
    //  *             ),
    //  *             @OA\Property(property="status", type="integer", example=2)
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return statuses updated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=true),
    //  *             @OA\Property(property="message", type="string", example="Return statuses updated.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Validation failed or bad request",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Invalid data provided.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Server error during update",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Update failed.")
    //  *         )
    //  *     )
    //  * )
    //  */
    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'return_ids' => 'required|array|min:1',
            'return_ids.*' => 'uuid|exists:return_header,uuid',
            'status' => 'required|integer',
        ]);

        $returnIds = $request->input('return_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($returnIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Return statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }

    // /**
    //  * @OA\Put(
    //  *     path="/api/agent_transaction/returns/update/{uuid}",
    //  *     summary="Update a return record",
    //  *     description="Updates an existing return entry using its UUID.",
    //  *     tags={"Return"},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the return record to update",
    //  *         @OA\Schema(type="string", format="uuid"),
    //  *         example="15d2a94a-334c-47be-b6f9-426afd9eeb71"
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="integer", example=1, description="Updated status of the return"),
    //  *             @OA\Property(property="remarks", type="string", example="Return approved"),
    //  *             @OA\Property(property="updated_by", type="integer", example=5)
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Return updated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="data", type="object",
    //  *                 @OA\Property(property="uuid", type="string", example="15d2a94a-334c-47be-b6f9-426afd9eeb71"),
    //  *                 @OA\Property(property="status", type="integer", example=1),
    //  *                 @OA\Property(property="remarks", type="string", example="Return approved")
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Return not found",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=404),
    //  *             @OA\Property(property="message", type="string", example="Return not found")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Validation or update failed",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="code", type="integer", example=400),
    //  *             @OA\Property(property="message", type="string", example="Failed to update return"),
    //  *             @OA\Property(property="error", type="string", example="SQLSTATE[23502]: Not null violation ...")
    //  *         )
    //  *     )
    //  * )
    //  */

    public function update(UpdateReturnRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Return not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new ReturnHeaderResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to update return',
                'error' => $e->getMessage()
            ], 400);
        }
    }


    public function exportReturnHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Agent_return_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'returnexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = !empty($filters['route_id'])
            ? explode(',', $filters['route_id'])
            : [];

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        // Location filter already tumne bana rakha hai
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);


        $export = new ReturnHeaderExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new ReturnHeaderExport();

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }
    public function exportReturnAll(Request $request)
    {
        $uuid   = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');
        $returnHerader = ReturnHeader::select('osa_code')
            ->where('uuid', $uuid)
            ->first();

        $code = $returnHerader?->osa_code ?? 'UNKNOWN';
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

        $filename = 'Agent_return_' . $code . '.' . $extension;
        $path = 'returnexports/' . $filename;
        // $filename  = 'return_all_export_' . now()->format('Ymd_His') . '.' . $extension;
        // $path      = 'returnexports/' . $filename;

        if ($format === 'csv' || $format === 'xlsx') {

            $export = new ReturnHeaderDetailExport($uuid);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
            } else {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
            }
        }
        if ($format === 'pdf') {
            $return = ReturnHeader::with([
                'warehouse',
                'customer',
                'details.item',
                'details.uom'
            ])->where('uuid', $uuid)->first();
            if (!$return) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Return not found.'
                ]);
            }
            $returnDetails = $return->details;
            $pdf = \PDF::loadView('return', [
                'return'        => $return,
                'returnDetails' => $returnDetails
            ])->setPaper('A4');
            \Storage::disk('public')->makeDirectory('returnexports');
            \Storage::disk('public')->put($path, $pdf->output());
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportReturnCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Agent_return_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'returnexports/' . $filename;



        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new ReturnCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new ReturnCollapseExport();

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }
    // public function getReturnsByCustomerUuid(Request $request, $uuid)
    // {
    //     $customer = AgentCustomer::where('uuid', trim($uuid))->first();

    //     if (!$customer) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 404,
    //             'message' => 'Customer not found for given UUID',
    //         ], 404);
    //     }

    //     $fromDate = $request->query('from_date');
    //     $toDate   = $request->query('to_date');
    //     $headersQuery = $customer->returnHeaders()->with('details');
    //     if ($fromDate) {
    //         $headersQuery->whereDate('created_at', '>=', $fromDate);
    //     }
    //     if ($toDate) {
    //         $headersQuery->whereDate('created_at', '<=', $toDate);
    //     }
    //     $headers = $headersQuery->get();
    //     if ($headers->isEmpty()) {
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'No return headers found for this customer',
    //             'data'    => [],
    //         ], 200);
    //     }

    //     return ReturnHeaderResource::collection($headers)->additional([
    //         'status'  => 'success',
    //         'code'    => 200,
    //         'message' => 'Returns retrieved successfully',
    //     ]);
    // }
    public function getReturnsByCustomerUuid(Request $request, $uuid)
    {
        $customer = AgentCustomer::where('uuid', trim($uuid))->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Customer not found for given UUID',
            ], 404);
        }

        $fromDate = $request->query('from_date');
        $toDate   = $request->query('to_date');

        $headersQuery = $customer->returnHeaders()->with('details');

        // 🔹 Both dates → range
        if ($fromDate && $toDate) {
            $headersQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }
        // 🔹 Only from_date
        elseif ($fromDate) {
            $headersQuery->whereDate('created_at', '>=', $fromDate);
        }
        // 🔹 Only to_date
        elseif ($toDate) {
            $headersQuery->whereDate('created_at', '<=', $toDate);
        }
        // 🔹 Default → current month
        else {
            $headersQuery->whereBetween('created_at', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString()
            ]);
        }

        $headers = $headersQuery->get();

        if ($headers->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'No return headers found for this customer',
                'data'    => [],
            ], 200);
        }

        return ReturnHeaderResource::collection($headers)->additional([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Returns retrieved successfully',
        ]);
    }

    public function exportReturnAgentCustomer(Request $request)
    {
        $uuid = $request->input('uuid');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'return_all_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'returnexports/' . $filename;

        $export = new ReturnAgentCustomerExport($uuid, $startDate, $endDate);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }


    public function returnlist()
    {
        try {
            $data = ReturnType::select('id', 'return_type')->orderBy('id', 'asc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Return types fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resionlist(Request $request)
    {
        try {
            // Get return_id from request
            $returnId = $request->input('return_id');

            // Build query
            $query = ResonType::select('id', 'return_id', 'reson')->orderBy('id', 'asc');

            // Apply filter if return_id is provided
            if ($returnId) {
                $query->where('return_id', $returnId);
            }

            $data = $query->get();

            return response()->json([
                'status' => true,
                'message' => 'Return types fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportReturnsByWarehouse(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $warehouseId = $request->warehouse_id;
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'return_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'returnexports/' . $filename;

        $export = new ReturnWarehouseCollapseExport($warehouseId);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    // public function getByWarehouse(Request $request)
    // {
    //     $warehouse_id = $request->get('warehouse_id');

    //     $limit = $request->get('limit', 10);

    //     $returns = ReturnHeader::with('details')
    //         ->where('warehouse_id', $warehouse_id)
    //         ->paginate($limit);

    //     $pagination = [
    //         'page'          => $returns->currentPage(),
    //         'limit'         => $returns->perPage(),
    //         'totalPages'    => $returns->lastPage(),
    //         'totalRecords'  => $returns->total(),
    //     ];

    //     return response()->json([
    //         'status'  => 'success',
    //         'code'    => 200,
    //         'message' => 'Returns fetched successfully',
    //         'data'       => ReturnHeaderResource::collection($returns->items()),
    //         'pagination' => $pagination,
    //     ]);
    // }

    public function getByWarehouse(Request $request)
    {
        $warehouse_id = $request->get('warehouse_id');
        $limit        = $request->get('limit', 10);

        $fromDate = $request->get('from_date');
        $toDate   = $request->get('to_date');

        $returnsQuery = ReturnHeader::with('details')
            ->where('warehouse_id', $warehouse_id);

        // ✅ DATE FILTER (created_at)
        if ($fromDate && $toDate) {
            $returnsQuery->whereDate('created_at', '>=', $fromDate)
                ->whereDate('created_at', '<=', $toDate);
        } elseif ($fromDate) {
            $returnsQuery->whereDate('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $returnsQuery->whereDate('created_at', '<=', $toDate);
        } else {
            // 🔹 Default → current month
            $returnsQuery->whereBetween('created_at', [
                now()->startOfMonth()->startOfDay(),
                now()->endOfMonth()->endOfDay(),
            ]);
        }

        $returns = $returnsQuery->paginate($limit);

        $pagination = [
            'page'         => $returns->currentPage(),
            'limit'        => $returns->perPage(),
            'totalPages'   => $returns->lastPage(),
            'totalRecords' => $returns->total(),
        ];

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Returns fetched successfully',
            'data'       => ReturnHeaderResource::collection($returns->items()),
            'pagination' => $pagination,
        ]);
    }

    public function getInoviceBasedReturn(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters  = $request->except(['limit', 'dropdown']);

            $invoices = $this->service->getInoviceBasedReturn($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $invoices,
                ]);
            }

            $resource = InvoiceHeaderBasedReturn::collection($invoices)->resolve();

            $filtered = collect($resource)
                ->filter(function ($invoice) {
                    return isset($invoice['details']) && count($invoice['details']) > 0;
                })
                ->values();

            $data = $filtered->isEmpty() ? null : $filtered;
            
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Invoices fetched successfully',
                'data'    => $data,
                // 'data'    => InvoiceHeaderBasedReturn::collection($invoices),
                'pagination'    => [
                    'page'         => $invoices->currentPage(),
                    'limit'        => $invoices->perPage(),
                    'totalPages'   => $invoices->lastPage(),
                    'totalRecords' => $invoices->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
