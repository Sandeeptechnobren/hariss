<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\StoreInvoiceRequest;
use App\Http\Requests\V1\Agent_Transaction\Mob\UpdateInvoiceRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\InvoiceHeaderResource;
use App\Services\V1\Agent_Transaction\Mob\InvoiceService;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\AgentCustomer;
// use App\Http\Resources\V1\Agent_Transaction\ClaimInvoiceDataResource;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    use ApiResponse;
    protected InvoiceService $service;

    public function __construct(InvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/invoices/create",
     *     summary="Create a new invoice",
     *     description="Creates a new invoice header along with its invoice details.",
     *     tags={"Mob Invoices"},
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
     *                 @OA\Property(property="item_quantity", type="number", example=10),
     *                 @OA\Property(property="promotion_id", type="integer", example=5, nullable=true)
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
        // dd($request->validated());
        try {
            $invoice = $this->service->create($request->validated());
            
            if (!$invoice) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create invoice'
                ], 400);
            }
            return response()->json([
                'status' => true,
                'code' => 201,
                'data' => new InvoiceHeaderResource($invoice)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * @OA\Get(
     *     path="/mob/master_mob/invoices/list",
     *     summary="Get all invoices",
     *     description="Retrieve a paginated list of invoices with optional filters and dropdown view.",
     *     tags={"Mob Invoices"},
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

    /**
     * @OA\Get(
     *     path="/mob/master_mob/invoices/show/{uuid}",
     *     summary="Get Invoice by UUID",
     *     description="Retrieve a specific invoice record along with all related data using its UUID.",
     *     tags={"Mob Invoices"},
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
     *     path="/mob/master_mob/invoices/delete/{uuid}",
     *     summary="Delete Invoice by UUID",
     *     description="Soft deletes an invoice and its related details based on the provided UUID.",
     *     tags={"Mob Invoices"},
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

    // /**
    //  * @OA\Post(
    //  *     path="/mob/master_mob/invoices/updatestatus",
    //  *     summary="Update status for multiple invoices",
    //  *     description="Updates the status of multiple invoices at once using their UUIDs.",
    //  *     tags={"Mob Invoices"},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             required={"invoice_ids","status"},
    //  *             @OA\Property(property="invoice_ids", type="array", @OA\Items(type="string", format="uuid"), example={"uuid-1","uuid-2"}),
    //  *             @OA\Property(property="status", type="integer", example=2)
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Invoice statuses updated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=true),
    //  *             @OA\Property(property="message", type="string", example="Invoice statuses updated.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=400,
    //  *         description="Validation error or bad request",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Validation failed.")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=500,
    //  *         description="Failed to update invoice statuses",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="success", type="boolean", example=false),
    //  *             @OA\Property(property="message", type="string", example="Update failed.")
    //  *         )
    //  *     )
    //  * )
    //  */
    // public function updateMultipleOrderStatus(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'invoice_ids' => 'required|array|min:1',
    //         'invoice_ids.*' => 'uuid|exists:invoice_headers,uuid',
    //         'status' => 'required|integer',
    //     ]);
    //     $invoiceIds = $request->input('invoice_ids');
    //     $status = $request->input('status');
    //     $result = $this->service->updateOrdersStatus($invoiceIds, $status);
    //     if ($result) {
    //         return response()->json(['success' => true, 'message' => 'Invoice statuses updated.'], 200);
    //     }
    //     return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
    // }

    /**
     * @OA\Put(
     *     path="/mob/master_mob/invoices/update/{uuid}",
     *     summary="Update an invoice by UUID",
     *     tags={"Mob Invoices"},
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
}