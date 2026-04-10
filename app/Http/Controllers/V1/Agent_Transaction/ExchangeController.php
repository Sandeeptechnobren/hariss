<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreExchangeRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateExchangeRequest;
use App\Http\Resources\V1\Agent_Transaction\ExchangeHeaderResource;
use App\Services\V1\Agent_Transaction\ExchangeService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use App\Exports\ExchangeHeaderExport;
use App\Exports\ExchangeAllExport;
use App\Exports\ExchangeCollapseExport;
use App\Models\Agent_Transaction\ExchangeHeader;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;


class ExchangeController extends Controller
{
    protected ExchangeService $service;

    public function __construct(ExchangeService $service)
    {
        $this->service = $service;
    }

    /**
     * Create a new Exchange Transaction
     *
     * @OA\Post(
     *     path="/api/agent_transaction/exchanges/create",
     *     summary="Create Exchange Transaction",
     *     description="Creates a new exchange transaction with its related invoices and returns.",
     *     operationId="createExchangeTransaction",
     *     tags={"Exchange"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Exchange transaction data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 example={
     *                     "currency": "USD",
     *                     "country_id": 1,
     *                     "order_id": 10,
     *                     "delivery_id": 5,
     *                     "warehouse_id": 2,
     *                     "route_id": 7,
     *                     "customer_id": 15,
     *                     "salesman_id": 3,
     *                     "gross_total": 500.00,
     *                     "vat": 25.00,
     *                     "net_amount": 475.00,
     *                     "total": 500.00,
     *                     "discount": 0,
     *                     "status": 1,
     *                     "invoices": {
     *                         {
     *                             "item_id": 101,
     *                             "uom_id": 1,
     *                             "discount_id": null,
     *                             "promotion_id": null,
     *                             "parent_id": null,
     *                             "item_price": 100,
     *                             "item_quantity": 5,
     *                             "vat": 5,
     *                             "discount": 0,
     *                             "gross_total": 500,
     *                             "net_total": 475,
     *                             "total": 500,
     *                             "is_promotional": false,
     *                             "status": 1
     *                         }
     *                     },
     *                     "returns": {
     *                         {
     *                             "item_id": 202,
     *                             "uom_id": 2,
     *                             "discount_id": null,
     *                             "promotion_id": null,
     *                             "parent_id": null,
     *                             "item_price": 50,
     *                             "item_quantity": 2,
     *                             "vat": 2.5,
     *                             "discount": 0,
     *                             "gross_total": 100,
     *                             "net_total": 95,
     *                             "total": 100,
     *                             "is_promotional": false,
     *                             "status": 1
     *                         }
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Exchange transaction created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data", type="object", description="Created exchange transaction with invoices and returns")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create exchange transaction",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to create exchange transaction"),
     *             @OA\Property(property="error", type="string", example="Validation error or exception message")
     *         )
     *     )
     * )
     */

    public function store(StoreExchangeRequest $request): JsonResponse
    {
        try {
            $exchange = $this->service->create($request->validated());
            if ($exchange) {
                LogHelper::store(
                    '13',
                    '83',
                    'add',
                    null,
                    $exchange->getAttributes(),
                    auth()->id()
                );
            }
            if (!$exchange) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Failed to create exchange transaction',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 201,
                'data'   => new ExchangeHeaderResource($exchange),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Failed to create exchange transaction',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a list of Exchange Transactions
     *
     * @OA\Get(
     *     path="/api/agent_transaction/exchanges/list",
     *     summary="List Exchange Transactions",
     *     security={{"bearerAuth":{}}},
     *     description="Retrieve a paginated list of exchange transactions. Supports pagination, dropdown mode, and filters.",
     *     operationId="getExchangeTransactions",
     *     tags={"Exchange"},
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page (default: 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="dropdown",
     *         in="query",
     *         description="If true, returns simplified data for dropdown lists",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Parameter(
     *         name="filters",
     *         in="query",
     *         description="Optional filter parameters (e.g. status, customer_id, route_id)",
     *         required=false,
     *         @OA\Schema(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Exchanges fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Exchanges fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object", example={
     *                     "uuid": "7e5a93a4-fbb8-46e0-a3a8-f9390c76f9e3",
     *                     "exchange_code": "EXCH-001",
     *                     "customer_id": 15,
     *                     "warehouse_id": 2,
     *                     "total": 500.00,
     *                     "status": 1
     *                 })
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 example={
     *                     "page": 1,
     *                     "limit": 50,
     *                     "totalPages": 10,
     *                     "totalRecords": 500
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve exchanges",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve exchanges"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42P01]: Undefined table: ...")
     *         )
     *     )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $filters  = $request->except(['limit', 'dropdown']);

            $exchanges = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $exchanges,
                ]);
            }

            $pagination = [
                'page'         => $exchanges->currentPage(),
                'limit'        => $exchanges->perPage(),
                'totalPages'   => $exchanges->lastPage(),
                'totalRecords' => $exchanges->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Exchanges fetched successfully',
                'data'    => ExchangeHeaderResource::collection($exchanges),
                'meta'    => $pagination,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve exchanges',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *
     * @OA\Get(
     *     path="/api/agent_transaction/exchanges/show/{uuid}",
     *     summary="Get Exchange Transaction by UUID",
     *     description="Retrieve detailed information about a specific exchange transaction using its UUID.",
     *     operationId="getExchangeTransactionByUuid",
     *     tags={"Exchange"},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the exchange transaction",
     *         @OA\Schema(type="string", format="uuid", example="7e5a93a4-fbb8-46e0-a3a8-f9390c76f9e3")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Exchange transaction retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={
     *                     "uuid": "7e5a93a4-fbb8-46e0-a3a8-f9390c76f9e3",
     *                     "exchange_code": "EXCH-001",
     *                     "currency": "USD",
     *                     "country_id": 1,
     *                     "warehouse_id": 2,
     *                     "customer_id": 15,
     *                     "gross_total": 500.00,
     *                     "vat": 25.00,
     *                     "net_amount": 475.00,
     *                     "total": 500.00,
     *                     "status": 1,
     *                     "created_at": "2025-10-29T10:35:00Z",
     *                     "updated_at": "2025-10-29T10:35:00Z"
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Exchange transaction not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Exchange transaction not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve exchange transaction",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve exchange transaction"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42P01]: Undefined table: ...")
     *         )
     *     )
     * )
     */

    public function show(string $uuid): JsonResponse
    {
        try {
            $exchange = $this->service->getByUuid($uuid);

            if (!$exchange) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Exchange transaction not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new ExchangeHeaderResource($exchange),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve exchange transaction',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *
     * @OA\Delete(
     *     path="/api/agent_transaction/exchange/delete/{uuid}",
     *     summary="Delete Exchange Transaction",
     *     description="Deletes an exchange transaction by its UUID. Returns 200 on success, 404 if not found, and 500 if an internal error occurs.",
     *     operationId="deleteExchangeTransaction",
     *     tags={"Exchange"},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the exchange transaction to delete",
     *         @OA\Schema(type="string", format="uuid", example="7e5a93a4-fbb8-46e0-a3a8-f9390c76f9e3")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Exchange transaction deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Exchange transaction deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Exchange transaction not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Exchange transaction not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete exchange transaction",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to delete exchange transaction"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[23503]: Foreign key violation ...")
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
                    'message' => 'Exchange transaction not found',
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Exchange transaction deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to delete exchange transaction',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *
     * @OA\Post(
     *     path="/api/agent_transaction/exchanges/updatestatus",
     *     summary="Update Multiple Exchange Order Statuses",
     *     description="Update the status of multiple exchange transactions at once by providing their UUIDs and a new status value.",
     *     operationId="updateMultipleExchangeOrderStatus",
     *     tags={"Exchange"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="List of exchange UUIDs and the new status value",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"exchange_ids", "status"},
     *                 example={
     *                     "exchange_ids": {
     *                         "7e5a93a4-fbb8-46e0-a3a8-f9390c76f9e3",
     *                         "cb8d2e33-2d76-4b94-8b1e-9a4c23b5328f"
     *                     },
     *                     "status": 2
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Exchange statuses updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Exchange statuses updated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid or missing fields)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The exchange_ids field is required."),
     *             @OA\Property(property="errors", type="object", example={
     *                 "exchange_ids": {"The exchange_ids field is required."},
     *                 "status": {"The status field must be an integer."}
     *             })
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update exchange statuses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed.")
     *         )
     *     )
     * )
     */

    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'exchange_ids' => 'required|array|min:1',
            'exchange_ids.*' => 'uuid|exists:exchange_headers,uuid',
            'status' => 'required|integer',
        ]);

        $exchangeIds = $request->input('exchange_ids');
        $status = $request->input('status');

        $result = $this->service->updateOrdersStatus($exchangeIds, $status);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Exchange statuses updated.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Update failed.'
        ], 500);
    }

    public function update(UpdateExchangeRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());

            if (!$updated) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Exchange transaction not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new ExchangeHeaderResource($updated),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Failed to update exchange transaction',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/agent_transaction/exchanges/export",
     *     summary="Export full exchange data",
     *     description="Export all exchange headers along with invoices and returns. Supports xlsx (default) and csv formats.",
     *     tags={"Exchange"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="format",
     *                 type="string",
     *                 enum={"xlsx", "csv"},
     *                 default="xlsx",
     *                 description="Format of the export file"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download initiated successfully",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request parameters",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */

    public function exportHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Exchange_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exchangeexports/' . $filename;


        $filters = $request->input('filter', []);
        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new ExchangeHeaderExport(
            $from,
            $to,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new ExchangeHeaderExport();

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

    public function exportAll(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'exchange_all_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exchangeallexports/' . $filename;

        $export = new ExchangeAllExport($uuid);

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

    public function exportAllCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'exchange_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exchangecollapseexports/' . $filename;

        $export = new ExchangeCollapseExport();

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


// public function exportExchangePdf(Request $request)
//     {
//         $uuid = $request->input('uuid');
//         if (!$uuid) {
//             return response()->json([
//                 'status'  => 'error',
//                 'message' => 'UUID is required'
//             ], 400);
//         }
//         $exchange = ExchangeHeader::with([
//             'warehouse',
//             'customer',
//             'route',
//             'salesman',
//             'country',
//             'invoices.item',
//             'invoices.inuoms',
//             'returns.item',
//             'returns.uoms',
//         ])->where('uuid', $uuid)->firstOrFail();
//         $exchangeHerader = ExchangeHeader::select('exchange_code')->where('uuid', $uuid)->first();
//         $code = $exchangeHerader?->exchange_code ?? 'UNKNOWN';
//         $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);
//         $filename = 'Agent_exchange_' . $code . '.pdf';
//         $path = 'exchangeexports/' . $filename;
//         $pdf = \PDF::loadView('exchange', [
//             'exchange'      => $exchange,
//             'invoiceItems'  => $exchange->invoices,
//             'returnItems'   => $exchange->returns,
//         ]);
//         \Storage::disk('public')->put($path, $pdf->output());
//         $appUrl = rtrim(config('app.url'), '/');
//         $fullUrl = $appUrl . '/storage/app/public/' . $path;

//         return response()->json([
//             'status' => 'success',
//             'download_url' => $fullUrl
//         ]);
//     }
// public function exportExchangePdf(Request $request)
// {
//     $uuid = $request->input('uuid');

//     if (!$uuid) {
//         return response()->json([
//             'status'  => 'error',
//             'message' => 'UUID is required'
//         ], 400);
//     }

//     $exchange = ExchangeHeader::with([
//         'warehouse',
//         'route',
//         'customer',
//         'salesman',
//         'country',
//         'createdBy',
//         'updatedBy',

//         'invoices.item',
//         // 'invoices.inuoms',
//         'invoices.discount',
//         'invoices.promotion',
//         'invoices.parent',

//         'returns.item',
//         'returns.uoms',
//         'returns.discount',
//         'returns.promotion',
//         'returns.parent',
//     ])
//     ->where('uuid', $uuid)
//     ->firstOrFail();

//     $code = optional($exchange)->exchange_code ?? 'UNKNOWN';
//     $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

//     $filename = 'Agent_exchange_' . $code . '.pdf';
//     $path = 'exchangeexports/' . $filename;

//     $pdf = \PDF::loadView('exchange', [
//         'exchange'      => $exchange,
//         'invoiceItems'  => $exchange->invoices,
//         'returnItems'   => $exchange->returns,
//     ]);
//     \Storage::disk('public')->put($path, $pdf->output());
//     $appUrl = rtrim(config('app.url'), '/');
//     $fullUrl = $appUrl . '/public/storage/' . $path;
//     return response()->json([
//         'status' => 'success',
//         'download_url' => $fullUrl
//     ]);
// }
public function exportExchangePdf(Request $request)
    {
        $uuid = $request->input('uuid');
        if (!$uuid) {
            return response()->json([
                'status'  => 'error',
                'message' => 'UUID is required'
            ], 400);
        }
        $exchange = $this->service->getByUuid($uuid);
        $code = $exchange->exchange_code ?? 'UNKNOWN';
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);
        $filename = 'Agent_exchange_' . $code . '.pdf';
        $path = 'exchangeexports/' . $filename;
        $pdf = \PDF::loadView('exchange', [
            'exchange'      => $exchange,
            'invoiceItems'  => $exchange->invoices, // Delivered
            'returnItems'   => $exchange->returns,  // Received
        ]);
        \Storage::disk('public')->put($path, $pdf->output());
        $fullUrl = url('storage/' . $path);
        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl
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

            $exchanges = $this->service->globalFilter($perPage, $filters);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Exchanges fetched successfully',
                'data'    => ExchangeHeaderResource::collection($exchanges),
                'pagination'    => [
                    'page'         => $exchanges->currentPage(),
                    'limit'        => $exchanges->perPage(),
                    'totalPages'   => $exchanges->lastPage(),
                    'totalRecords' => $exchanges->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve exchanges',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
