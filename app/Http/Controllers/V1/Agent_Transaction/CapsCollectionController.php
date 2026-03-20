<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreCapsCollectionRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateCapsCollectionRequest;
use App\Http\Resources\V1\Agent_Transaction\CapsCollectionHeaderResource;
use App\Services\V1\Agent_Transaction\CapsCollectionService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\CapsCollectionFullExport;
use App\Exports\CapsCollectionDetailHeaderExport;
use App\Exports\CapsCollectionCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;
use Illuminate\Pagination\Paginator;


class CapsCollectionController extends Controller
{
    protected CapsCollectionService $service;

    public function __construct(CapsCollectionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/capscollection/create",
     *     tags={"Caps Collection"},
     *     summary="Create a new caps collection transaction",
     *     description="Creates a new caps collection header with its associated details.",
     *     operationId="storeCapsCollection",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Caps collection creation payload",
     *         @OA\JsonContent(
     *             example={
     *                 "warehouse_id": 113,
     *                 "route_id": 54,
     *                 "salesman_id": 113,
     *                 "customer": "John Doe Supermarket",
     *                 "status": 1,
     *                 "details": {
     *                     {
     *                         "item_id": 45,
     *                         "uom_id": 3,
     *                         "collected_quantity": 10,
     *                         "status": 1
     *                     },
     *                     {
     *                         "item_id": 46,
     *                         "uom_id": 2,
     *                         "collected_quantity": 5,
     *                         "status": 1
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Caps collection transaction created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create caps collection transaction"
     *     )
     * )
     */

    public function store(StoreCapsCollectionRequest $request): JsonResponse
    {
        try {
            $collection = $this->service->create($request->validated());
            if ($collection) {
                LogHelper::store(
                    '13',
                    '73',
                    'add',
                    null,
                    $collection->getAttributes(),
                    auth()->id()
                );
            }

            if (!$collection) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create caps collection transaction',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'data' => new CapsCollectionHeaderResource($collection),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create caps collection transaction',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/capscollection/list",
     *     tags={"Caps Collection"},
     *     summary="Get list of caps collection transactions",
     *     description="Fetches a paginated list of caps collection headers with optional filters and dropdown mode.",
     *     operationId="getCapsCollections",
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of records per page (default: 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="dropdown",
     *         in="query",
     *         required=false,
     *         description="If true, returns a simple dropdown-style list",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         required=false,
     *         description="Filter by warehouse ID",
     *         @OA\Schema(type="integer", example=113)
     *     ),
     *     @OA\Parameter(
     *         name="route_id",
     *         in="query",
     *         required=false,
     *         description="Filter by route ID",
     *         @OA\Schema(type="integer", example=54)
     *     ),
     *     @OA\Parameter(
     *         name="salesman_id",
     *         in="query",
     *         required=false,
     *         description="Filter by salesman ID",
     *         @OA\Schema(type="integer", example=113)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status (1 = active, 0 = inactive)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Caps collections fetched successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "message": "Caps collections fetched successfully",
     *                 "data": {
     *                     {
     *                         "id": 6,
     *                         "uuid": "7a9c7156-2c16-4874-95f4-5318e45e0b92",
     *                         "code": "CAPHD-001",
     *                         "warehouse_name": "Test",
     *                         "route_name": "qwertyui",
     *                         "salesman_name": "ewrtwer",
     *                         "customer": "John Doe Supermarket",
     *                         "status": 1
     *                     }
     *                 },
     *                 "meta": {
     *                     "page": 1,
     *                     "limit": 50,
     *                     "totalPages": 1,
     *                     "totalRecords": 10
     *                 }
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve caps collections"
     *     )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters = $request->except(['limit', 'dropdown']);

            $collections = $this->service->getAll($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $collections,
                ]);
            }

            $pagination = [
                'currentPage' => $collections->currentPage(),
                'perPage'     => $collections->perPage(),
                'lastPage'    => $collections->lastPage(),
                'total'       => $collections->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Caps collections fetched successfully',
                'data'    => CapsCollectionHeaderResource::collection($collections),
                'pagination'    => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve caps collections',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/capscollection/show/{uuid}",
     *     tags={"Caps Collection"},
     *     summary="Get a single caps collection transaction by UUID",
     *     description="Fetches the details of a specific caps collection header along with its details.",
     *     operationId="getCapsCollectionByUuid",
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the caps collection record",
     *         @OA\Schema(type="string", example="7a9c7156-2c16-4874-95f4-5318e45e0b92")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Caps collection fetched successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "data": {
     *                     "id": 6,
     *                     "uuid": "7a9c7156-2c16-4874-95f4-5318e45e0b92",
     *                     "code": "CAPHD-001",
     *                     "warehouse_id": 113,
     *                     "warehouse_code": "WH000230",
     *                     "warehouse_name": "Test Warehouse",
     *                     "route_id": 54,
     *                     "route_code": "RT000149",
     *                     "route_name": "Route A",
     *                     "salesman_id": 113,
     *                     "salesman_code": "SM000340201",
     *                     "salesman_name": "John Smith",
     *                     "customer": "John Doe Supermarket",
     *                     "status": 1,
     *                     "details": {
     *                         {
     *                             "id": 12,
     *                             "uuid": "2c8b7f63-41da-41ce-9459-83b57e38e0b5",
     *                             "header_id": 6,
     *                             "item_id": 33,
     *                             "item_code": "ITM00033",
     *                             "item_name": "Bottle Cap 500ml",
     *                             "uom_id": 5,
     *                             "uom_name": "PCS",
     *                             "collected_quantity": 200,
     *                             "status": 1
     *                         }
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Caps collection transaction not found",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 404,
     *                 "message": "Caps collection transaction not found"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve caps collection transaction",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 500,
     *                 "message": "Failed to retrieve caps collection transaction",
     *                 "error": "SQLSTATE[XX000]: example database error message"
     *             }
     *         )
     *     )
     * )
     */

    public function show(string $uuid): JsonResponse
    {
        try {
            $collection = $this->service->getByUuid($uuid);

            if (!$collection) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Caps collection transaction not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new CapsCollectionHeaderResource($collection),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve caps collection transaction',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/capscollection/delete/{uuid}",
     *     tags={"Caps Collection"},
     *     summary="Delete a caps collection transaction",
     *     description="Deletes a specific caps collection transaction by its UUID.",
     *     operationId="deleteCapsCollection",
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the caps collection transaction to delete",
     *         @OA\Schema(type="string", example="7a9c7156-2c16-4874-95f4-5318e45e0b92")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Caps transaction deleted successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "message": "Caps transaction deleted successfully"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Caps transaction not found",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 404,
     *                 "message": "Caps transaction not found"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete caps transaction",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 500,
     *                 "message": "Failed to delete caps transaction",
     *                 "error": "SQLSTATE[XX000]: example database error message"
     *             }
     *         )
     *     )
     * )
     */

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $result = $this->service->delete($uuid);

            if (! $result) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Caps transaction not found'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Caps transaction deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to delete caps transaction',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/capscollection/updatestatus",
     *     tags={"Caps Collection"},
     *     summary="Update status of multiple caps collection orders",
     *     description="Updates the status field for multiple caps collection transactions based on their UUIDs.",
     *     operationId="updateMultipleCapsOrderStatus",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of cap collection UUIDs and new status value",
     *         @OA\JsonContent(
     *             example={
     *                 "cap_ids": {
     *                     "7a9c7156-2c16-4874-95f4-5318e45e0b92",
     *                     "b9a272f3-6fd9-4453-a6b7-03c3b9a9d4fa"
     *                 },
     *                 "status": 2
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Statuses updated successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "success": true,
     *                 "message": "Caps statuses updated."
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid input or missing fields)",
     *         @OA\JsonContent(
     *             example={
     *                 "message": "The given data was invalid.",
     *                 "errors": {
     *                     "cap_ids": {"The cap_ids field is required."},
     *                     "status": {"The status field is required."}
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update caps statuses",
     *         @OA\JsonContent(
     *             example={
     *                 "success": false,
     *                 "message": "Update failed."
     *             }
     *         )
     *     )
     * )
     */

    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'cap_ids' => 'required|array|min:1',
            'cap_ids.*' => 'uuid|exists:caps_collection_headers,uuid',
            'status' => 'required|integer',
        ]);

        $capIds = $request->input('cap_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($capIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Caps statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/capscollection/update/{uuid}",
     *     tags={"Caps Collection"},
     *     summary="Update an existing caps collection transaction",
     *     description="Updates header and details information of a caps collection transaction using its UUID.",
     *     operationId="updateCapsCollection",
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the caps collection transaction to update",
     *         @OA\Schema(type="string"),
     *         example="7a9c7156-2c16-4874-95f4-5318e45e0b92"
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated caps collection header and detail data",
     *         @OA\JsonContent(
     *             example={
     *                 "warehouse_id": 1,
     *                 "route_id": 5,
     *                 "salesman_id": 3,
     *                 "customer": "John Doe Retailer",
     *                 "status": 2,
     *                 "details": {
     *                     {
     *                         "id": 10,
     *                         "item_id": 101,
     *                         "uom_id": 2,
     *                         "collected_quantity": 50,
     *                         "status": 1
     *                     },
     *                     {
     *                         "item_id": 102,
     *                         "uom_id": 3,
     *                         "collected_quantity": 25,
     *                         "status": 1
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Caps collection updated successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "success",
     *                 "code": 200,
     *                 "data": {
     *                     "uuid": "7a9c7156-2c16-4874-95f4-5318e45e0b92",
     *                     "code": "CAP-00045",
     *                     "warehouse_id": 1,
     *                     "route_id": 5,
     *                     "salesman_id": 3,
     *                     "customer": "John Doe Retailer",
     *                     "status": 2,
     *                     "details": {
     *                         {
     *                             "id": 10,
     *                             "item_id": 101,
     *                             "uom_id": 2,
     *                             "collected_quantity": 50,
     *                             "status": 1
     *                         },
     *                         {
     *                             "id": 11,
     *                             "item_id": 102,
     *                             "uom_id": 3,
     *                             "collected_quantity": 25,
     *                             "status": 1
     *                         }
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Caps collection not found",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 404,
     *                 "message": "Caps collection not found"
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Failed to update caps collection",
     *         @OA\JsonContent(
     *             example={
     *                 "status": "error",
     *                 "code": 400,
     *                 "message": "Failed to update caps collection",
     *                 "error": "Validation or database error details"
     *             }
     *         )
     *     )
     * )
     */

    public function update(UpdateCapsCollectionRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Caps collection not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new CapsCollectionHeaderResource($updated),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Failed to update caps collection',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/capscollection/export",
     *     summary="Export full Caps Collection data",
     *     description="Exports all Caps Collection headers and details as XLSX or CSV file.",
     *     tags={"Caps Collection"},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="File format: xlsx (default) or csv",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"xlsx","csv"},
     *             default="xlsx"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="url",
     *                 type="string",
     *                 description="Public URL of the exported file"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid format parameter",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function exportCapsCollection(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Caps_collection_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'capscollectionexports/' . $filename;

        $filters = $request->input('filter', []);
        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new CapsCollectionFullExport(
            $from,
            $to,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new CapsCollectionFullExport();

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
    public function exportCapsCollectionHeader(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'caps_collection_header_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'capscollectionexports/' . $filename;

        $export = new CapsCollectionDetailHeaderExport($uuid);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'uuid' => $uuid,
            'download_url' => $fullUrl,
        ]);
    }
    public function exportCapsCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Caps_collapse_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'capscollectionexports/' . $filename;

        $filters = $request->input('filter', []);
        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new CapsCollectionCollapseExport(
            $from,
            $to,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new CapsCollectionCollapseExport();

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

    public function getQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'required|integer',
            'item_id'      => 'required|integer',
        ]);

        $data = $this->service->getQtyByWarehouseAndItem(
            $request->warehouse_id,
            $request->item_id
        );

        if (! $data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No quantity found for given warehouse and item'
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }



    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = max((int) $request->input('per_page', 50), 1);
            $page    = max((int) $request->input('current_page', 1), 1);

            $filters = [
                'filter'       => $request->input('filter', []),
                'current_page' => $page,
                'per_page'     => $perPage,
            ];

            $collections = $this->service->globalFilter($filters);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Caps collections fetched successfully',
                'data'    => CapsCollectionHeaderResource::collection($collections),
                'pagination' => [
                    'currentPage' => $collections->currentPage(),
                    'perPage'     => $collections->perPage(),
                    'lastPage'    => $collections->lastPage(),
                    'total'       => $collections->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve caps collections',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
