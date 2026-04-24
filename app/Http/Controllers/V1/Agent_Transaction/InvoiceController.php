<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\StoreInvoiceRequest;
use App\Http\Requests\V1\Agent_Transaction\UpdateInvoiceRequest;
use App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderResource;
use App\Services\V1\Agent_Transaction\InvoiceService;
use App\Services\V1\EfrisAPI\UraInvoiceService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\AgentCustomer;
use App\Exports\InvoiceHeaderExport;
use App\Exports\InvoiceDetailHeaderExport;
use App\Exports\InvoiceHeaderWarehouseExport;
use App\Exports\InvoiceCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;
use App\Exports\InvoiceAgentCustomerExport;
use App\Helpers\LogHelper;
use App\Exports\InvoiceWarehouseCollapseExport;
use App\Helpers\CommonLocationFilter;
use App\Exports\InvoicePromotionExport;

class InvoiceController extends Controller
{
    use ApiResponse;
    protected InvoiceService $service;
    protected UraInvoiceService $serviceEfris;

    public function __construct(InvoiceService $service, UraInvoiceService $serviceEfris)
    {
        $this->service = $service;
        $this->serviceEfris = $serviceEfris;
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/invoices/create",
     *     summary="Create a new invoice",
     *     description="Creates a new invoice header along with its invoice details.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Invoice creation payload",
     *         @OA\JsonContent(
     *             required={"currency","country_id","warehouse_id","customer_id","salesman_id","delivery_date","details"},
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="warehouse_id", type="integer", example=3),
     *             @OA\Property(property="route_id", type="integer", example=2, nullable=true),
     *             @OA\Property(property="customer_id", type="integer", example=15),
     *             @OA\Property(property="salesman_id", type="integer", example=7),
     *             @OA\Property(property="delivery_date", type="string", format="date", example="2025-10-26"),
     *             @OA\Property(property="details", type="array", @OA\Items(
     *                 type="object",
     *                 required={"item_id","uom_id","item_price","item_quantity"},
     *                 @OA\Property(property="item_id", type="integer", example=101),
     *                 @OA\Property(property="uom_id", type="integer", example=2),
     *                 @OA\Property(property="item_price", type="number", example=100),
     *                 @OA\Property(property="item_quantity", type="number", example=10)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to create invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to create invoice"),
     *             @OA\Property(property="error", type="string", example="Validation error or exception message")
     *         )
     *     )
     * )
     */

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->create($request->validated());
        LogHelper::store(
            '13',
            '39',
            'add',
            null,
            $invoice->getAttributes(),
            auth()->id()
        );
        try {
            $invoiceId = $invoice->id;

            // small delay (optional but powerful)
            usleep(200000); // 0.2 sec

            $response = $this->serviceEfris->syncInvoice($invoiceId);

            $isSuccess =
                ($response['returnCode'] ?? null) === "00" &&
                strtoupper($response['message'] ?? '') === "SUCCESS";
        } catch (\Throwable $e) {

            \Log::error('Auto Sync Failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);

            $isSuccess = false;
            $response = [
                'message' => $e->getMessage()
            ];
        }
        return response()->json([
            'status' => 'success',
            'code'   => 201,
            'data'   => new InvoiceHeaderResource($invoice)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/invoices/list",
     *     summary="Get all invoices",
     *     description="Retrieve a paginated list of invoices with optional filters and dropdown view.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of records per page (default 50)",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="dropdown",
     *         in="query",
     *         required=false,
     *         description="If true, returns simplified data for dropdown (no pagination).",
     *         @OA\Schema(type="boolean", example=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoices fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Invoices fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="totalPages", type="integer", example=10),
     *                 @OA\Property(property="totalRecords", type="integer", example=500)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve invoices",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve invoices"),
     *             @OA\Property(property="error", type="string", example="SQLSTATE[42703]: Undefined column...")
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
            $invoices = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $invoices,
                ]);
            }
            $pagination = [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
                'total'        => $invoices->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Invoices fetched successfully',
                'data'    => InvoiceHeaderResource::collection($invoices),
                'pagination'  => $pagination,
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

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $filters = $request->except(['limit']);

            $invoices = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
                'total'        => $invoices->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'message'    => 'Invoices fetched successfully',
                'data'       => InvoiceHeaderResource::collection($invoices),
                'pagination' => $pagination,
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

    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = $request->get('limit', 50);
    //         $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
    //         $filters = $request->except(['limit', 'dropdown']);
    //         $invoices = $this->service->getAll($perPage, $filters, $dropdown);
    //         if ($dropdown) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'code'   => 200,
    //                 'data'   => $invoices,
    //             ]);
    //         }
    //         $pagination = [
    //             'page'         => $invoices->currentPage(),
    //             'limit'        => $invoices->perPage(),
    //             'totalPages'   => $invoices->lastPage(),
    //             'totalRecords' => $invoices->total(),
    //         ];
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Invoices fetched successfully',
    //             'data'    => InvoiceHeaderResource::collection($invoices),
    //             // 'data'    => $invoices,

    //             'meta'    => $pagination,
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Failed to retrieve invoices',
    //             'error'   => $e->getMessage(),
    //         ]);
    //     }
    // }
    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = $request->get('limit', 50);
    //         $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
    //         $filters = $request->except(['limit', 'dropdown']);
    //         $invoices = $this->service->getAll($perPage, $filters, $dropdown);
    //         if ($dropdown) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'code'   => 200,
    //                 'data'   => $invoices,
    //             ]);
    //         }
    //         if ($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator) {
    //             $invoices->setCollection(
    //                 collect($invoices->items())->map(function ($row) {
    //                     $model = new \App\Models\Agent_Transaction\InvoiceHeader((array) $row);
    //                     $model->exists = true;
    //                     return $model;
    //                 })
    //             );
    //         }
    //         $pagination = [
    //             'page'         => $invoices->currentPage(),
    //             'limit'        => $invoices->perPage(),
    //             'totalPages'   => $invoices->lastPage(),
    //             'totalRecords' => $invoices->total(),
    //         ];
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Invoices fetched successfully',
    //             'data'    => \App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderResource::collection($invoices),
    //             'meta'    => $pagination,
    //         ]);
    //     } catch (\Throwable $e) {
    //         \Log::error('Invoice fetch failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Failed to retrieve invoices',
    //             'error'   => $e->getMessage(),
    //         ]);
    //     }
    // }
    public function exportInvoiceByPromotionCollapse(Request $request)
    {
        $request->validate([
            'promotion_id' => 'required|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'format' => 'nullable|string|in:xlsx,csv',
        ]);

        $promotionId = $request->input('promotion_id');

        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'invoice_headers_promotion_' . $promotionId . '_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        // Create export instance
        $export = new InvoicePromotionExport(
            $request->input('from'),
            $request->input('to'),
            $promotionId
        );

        // Store file
        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/agent_transaction/invoices/show/{uuid}",
     *     summary="Get Invoice by UUID",
     *     description="Retrieve a specific invoice record along with all related data using its UUID.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Invoice not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve invoice"),
     *             @OA\Property(property="error", type="string", example="Exception message")
     *         )
     *     )
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $invoice = $this->service->getByUuid($uuid);
            if (!$invoice) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Invoice not found'
                ], 404);
            }
            $invoice['current']->previous_uuid = $invoice['previous'] ?? null;
            $invoice['current']->next_uuid     = $invoice['next'] ?? null;
            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new InvoiceHeaderResource($invoice['current']),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoice',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/invoices/delete/{uuid}",
     *     summary="Delete Invoice by UUID",
     *     description="Soft deletes an invoice and its related details based on the provided UUID.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Invoice deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Invoice not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to delete invoice"),
     *             @OA\Property(property="error", type="string", example="Exception message")
     *         )
     *     )
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $result = $this->service->delete($uuid);
            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Invoice not found'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/agent_transaction/invoices/updatestatus",
     *     summary="Update status for multiple invoices",
     *     description="Updates the status of multiple invoices at once using their UUIDs.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"invoice_ids","status"},
     *             @OA\Property(property="invoice_ids", type="array", @OA\Items(type="string", format="uuid"), example={"uuid-1","uuid-2"}),
     *             @OA\Property(property="status", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice statuses updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice statuses updated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update invoice statuses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed.")
     *         )
     *     )
     * )
     */
    public function updateMultipleOrderStatus(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'uuid|exists:invoice_headers,uuid',
            'status' => 'required|integer',
        ]);
        $invoiceIds = $request->input('invoice_ids');
        $status = $request->input('status');
        $result = $this->service->updateOrdersStatus($invoiceIds, $status);
        if ($result) {
            return response()->json(['success' => true, 'message' => 'Invoice statuses updated.'], 200);
        }
        return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/invoices/update/{uuid}",
     *     summary="Update an invoice by UUID",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="invoice_code", type="string", example="INV-2025-0001"),
     *             @OA\Property(property="customer_id", type="integer", example=3),
     *             @OA\Property(property="salesman_id", type="integer", example=2),
     *             @OA\Property(property="invoice_date", type="string", format="date", example="2025-10-25"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="gross_total", type="number", example=1000),
     *             @OA\Property(property="vat", type="number", example=50),
     *             @OA\Property(property="discount", type="number", example=20),
     *             @OA\Property(property="net_amount", type="number", example=1030),
     *             @OA\Property(property="total", type="number", example=1050),
     *             @OA\Property(property="details", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invoice not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Invoice not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to update invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Failed to update invoice"),
     *             @OA\Property(property="error", type="string", example="Validation error or exception message")
     *         )
     *     )
     * )
     */
    public function update(UpdateInvoiceRequest $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->update($uuid, $request->validated());
            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Invoice not found'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => new InvoiceHeaderResource($updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/invoices/export",
     *     summary="Export full Invoice data",
     *     description="Exports all invoice headers as XLSX or CSV file.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="File format: xlsx (default) or csv",
     *         @OA\Schema(type="string", enum={"xlsx","csv"}, default="xlsx")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download response (Excel or CSV)"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to export invoices"),
     *             @OA\Property(property="error", type="string", example="Exception message")
     *         )
     *     )
     * )
     */
    public function exportInvoiceHeader(Request $request)
    {
        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $date = now()->format('Ymd_His'); // 20260408_143210
        $random = rand(1000, 9999);

        $filename = "Agent_invoice_header_{$date}_{$random}.{$extension}";
        $path = 'invoiceexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        // ✅ Parse route & salesman
        $routeIds = !empty($filters['route_id'])
            ? array_map('intval', explode(',', $filters['route_id']))
            : [];

        $salesmanIds = !empty($filters['salesman_id'])
            ? array_map('intval', explode(',', $filters['salesman_id']))
            : [];

        $resolvedWarehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $inputWarehouseIds = !empty($filters['warehouse_id'])
            ? array_map('intval', explode(',', $filters['warehouse_id']))
            : [];

        if (!empty($inputWarehouseIds)) {
            $warehouseIds = array_values(array_intersect($resolvedWarehouseIds, $inputWarehouseIds));
        } else {
            $warehouseIds = $resolvedWarehouseIds;
        }


        $export = new InvoiceHeaderExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            [],
            $salesmanIds
        );

        if ($format === 'csv') {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }


    public function exportInvoiceCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filters = $request->input('filter', []);

        $from = $filters['from_date'] ?? null;
        $to   = $filters['to_date'] ?? null;

        // ✅ Normalize IDs
        $routeIds = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);

        // 🔥 STEP 1: resolve (broad)
        $resolvedWarehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        // 🔥 STEP 2: exact warehouse from payload
        $inputWarehouseIds = CommonLocationFilter::normalizeIds($filters['warehouse_id'] ?? []);

        // 🔥 STEP 3: FINAL warehouseIds (intersection)
        if (!empty($inputWarehouseIds)) {
            $warehouseIds = array_values(array_intersect($resolvedWarehouseIds, $inputWarehouseIds));
        } else {
            $warehouseIds = $resolvedWarehouseIds;
        }

        // 🔥 Filename with date + random
        $date = now()->format('Ymd_His'); // 20260408_143210
        $random = rand(1000, 9999);

        $filename = "Agent_invoice_collapse_{$date}_{$random}.{$extension}";
        $path = 'invoiceexports/' . $filename;

        // ❌ routeIds remove (already resolved via warehouse)
        $export = new InvoiceCollapseExport(
            $from,
            $to,
            $warehouseIds,
            [], // important
            $salesmanIds
        );

        if ($format === 'csv') {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            \Maatwebsite\Excel\Facades\Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/agent_transaction/invoices/exportFull",
     *     summary="Export Invoice header + detail data",
     *     description="Exports all invoice headers and details as XLSX or CSV file.",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="File format: xlsx (default) or csv",
     *         @OA\Schema(type="string", enum={"xlsx","csv"}, default="xlsx")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download response (Excel or CSV)"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to export invoices"),
     *             @OA\Property(property="error", type="string", example="Exception message")
     *         )
     *     )
     * )
     */
    public function exportInvoiceFullExport(Request $request)
    {
        $uuid     = $request->input('uuid');
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $format = strtolower($request->input('format', 'xlsx'));

        $extension = $format === 'csv'
            ? 'csv'
            : ($format === 'pdf' ? 'pdf' : 'xlsx');

        $invoiceHeader = InvoiceHeader::select('invoice_code')
            ->where('uuid', $uuid)
            ->first();

        // $code = $invoiceHeader?->invoice_code ?? 'UNKNOWN';
        $code = $invoiceHeader?->invoice_code ?? 'INV-' . time();
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

        if ($extension === 'pdf') {
            $filename = 'Agent_invoice_' . $code . '.pdf';
        } else {
            $filename = 'Agent_invoice_all.' . $extension;
        }
        $path = 'invoiceexports/' . $filename;
        if ($format === 'csv' || $format === 'xlsx') {

            $export = new InvoiceDetailHeaderExport($uuid, $fromDate, $toDate);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
            } else {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
            }
        }

        if ($format === 'pdf') {

            $header = InvoiceHeader::with([
                'warehouse',
                'customer',
                'details.item',
                'details.uoms',
                'salesman',
                'salesman.salesmanType'
            ])->where('uuid', $uuid)->first();
            // dd($header); 
            if (!$header) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invoice not found.'
                ]);
            }

            $detailsQuery = $header->details();

            if ($fromDate && $toDate) {
                $detailsQuery->whereBetween('created_at', [$fromDate, $toDate]);
            }

            $details = $detailsQuery->get();
            $pdf = \PDF::loadView('invoice', [
                'header'  => $header,
                'details' => $details,
            ])->setPaper('A4');

            \Storage::disk('public')->makeDirectory('invoiceexports');

            \Storage::disk('public')->put($path, $pdf->output());
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }


    public function getInvoicesByCustomerUuid(Request $request, $uuid)
    {
        $uuid = trim($uuid);
        if (!Str::isUuid($uuid)) {
            return response()->json([
                'status'  => 'error',
                'code'    => 400,
                'message' => 'Invalid UUID format',
            ], 400);
        }
        $customer = AgentCustomer::where('uuid', $uuid)->first();
        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Agent customer not found',
            ], 404);
        }
        $query = $customer->invoiceHeaders()->with('details');
        // if ($request->has('from_date') && $request->has('to_date')) {
        //     $fromDate = $request->input('from_date');
        //     $toDate = $request->input('to_date');
        //     if (!$this->isValidDate($fromDate) || !$this->isValidDate($toDate)) {
        //         return response()->json([
        //             'status'  => 'error',
        //             'code'    => 400,
        //             'message' => 'Invalid date format, use YYYY-MM-DD.',
        //         ], 400);
        //     }
        //     $query->whereBetween('invoice_date', [$fromDate, $toDate]);
        // }
        if ($request->has('from_date') && $request->has('to_date')) {

            $fromDate = $request->input('from_date');
            $toDate   = $request->input('to_date');

            if (!$this->isValidDate($fromDate) || !$this->isValidDate($toDate)) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'Invalid date format, use YYYY-MM-DD.',
                ], 400);
            }

            $query->whereBetween('invoice_date', [$fromDate, $toDate]);
        } else {
            // 🔹 Default = Current Month
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth   = now()->endOfMonth()->toDateString();

            $query->whereBetween('invoice_date', [$startOfMonth, $endOfMonth]);
        }

        $invoices = $query->get();
        if ($invoices->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'No invoices found for this customer',
                'data'    => [],
            ]);
        }
        return InvoiceHeaderResource::collection($invoices)->additional([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Invoices retrieved successfully',
        ]);
    }
    private function isValidDate($date)
    {
        $format = 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function exportInvoiceByWarehouse(Request $request, $warehouse_id)
    {
        $warehouseId = $warehouse_id;

        $format      = strtolower($request->input('format', 'xlsx'));
        $extension   = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'invoice_headers_warehouse_' . $warehouseId . '_' . now()->format('Ymd_His') . '.' . $extension;
        $path     = 'invoiceexports/' . $filename;

        $export = new InvoiceHeaderWarehouseExport($warehouseId);

        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportInvoiceAgentCustomer(Request $request, $uuid)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'invoice_all_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        $export = new InvoiceAgentCustomerExport($uuid, $startDate, $endDate);

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
    public function exportInvoicesByWarehouse(Request $request)
    {
        $warehouseId = $request->query('warehouse_id') ?? $request->input('warehouse_id');
        $startDate = $request->query('start_date') ?? $request->input('start_date');
        $endDate = $request->query('end_date') ?? $request->input('end_date');

        $request->merge([
            'warehouse_id' => $warehouseId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $rules = [
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'format' => 'nullable|in:xlsx,csv',
        ];
        if ($startDate) {
            $rules['start_date'] = 'date';
        }
        if ($endDate) {
            $rules['end_date'] = 'date|after_or_equal:start_date';
        }

        $request->validate($rules);

        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'invoice_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'invoiceexports/' . $filename;

        $export = new InvoiceWarehouseCollapseExport($warehouseId, $startDate, $endDate);

        Excel::store(
            $export,
            $path,
            'public',
            $format === 'csv'
                ? \Maatwebsite\Excel\Excel::CSV
                : \Maatwebsite\Excel\Excel::XLSX
        );

        $appUrl = rtrim(config('app.url'), '/');
        $downloadUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status' => 'success',
            'warehouse_id' => $warehouseId,
            'download_url' => $downloadUrl,
            //  'start_date' => $startDate,
            //  'end_date' => $endDate,
        ]);
    }

    public function InvoicesByPromotion(Request $request)
    {
        try {
            $request->validate([
                'promotion_id' => 'required|integer'
            ]);

            $perPage = $request->get('per_page', 10);

            $invoices = $this->service
                ->getInvoicesByPromotion($request->promotion_id, $perPage);

            $pagination = [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'per_page'     => $invoices->perPage(),
                'total'        => $invoices->total(),
            ];

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Invoices fetched successfully',
                'data'    => InvoiceHeaderResource::collection($invoices),
                'pagination'  => $pagination,
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
