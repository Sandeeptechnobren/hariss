<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Exports\AgentDeliveryExport;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\AgentDeliveryHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\AgentDeliveryHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\AgentDeliveryHeaderResource;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Services\V1\Agent_Transaction\AgentDeliveryHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryHeaderExport;
use App\Exports\DeliveryCollapseExport;
use App\Exports\DeliveryFulllExport;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @OA\Tag(
 *     name="Agent Delivery Header",
 *     description="API endpoints for managing Delivery Headers and their Details"
 * )
 */
class AgentDeliveryHeaderController extends Controller
{
    public function __construct(protected AgentDeliveryHeaderService $service) {}

    // /**
    //  * @OA\Get(
    //  *     path="/api/agent_transaction/agent-delivery/list",
    //  *     tags={"Agent Delivery Header"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="List all agent deliveries",
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of all agent deliveries"
    //  *     )
    //  * )
    //  */
    // public function index(): JsonResponse
    // {
    //     try {
    //         $headers = $this->service->all();
    //         return ResponseHelper::paginatedResponse(
    //             'Deliveries fetched successfully',
    //             AgentDeliveryHeaderResource::class,
    //             $headers
    //         );
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/agent-delivery/list",
     *     tags={"Agent Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="List all agent deliveries",
     *     @OA\Response(
     *         response=200,
     *         description="List of all agent deliveries"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->all($perPage);
            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function globalFilter(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->globalFilter($perPage);

            return ResponseHelper::paginatedResponse(
                'Deliveries fetched successfully',
                AgentDeliveryHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/agent_transaction/agent-delivery/add",
     *     tags={"Agent Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new delivery with details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"warehouse_id","route_id","salesman_id","details"},
     *             @OA\Property(property="warehouse_id", type="integer", example=101),
     *             @OA\Property(property="route_id", type="integer", example=22),
     *             @OA\Property(property="salesman_id", type="integer", example=45),
     *             @OA\Property(property="customer_id", type="integer", example=89),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="gross_total", type="number", format="float", example=1000.00),
     *             @OA\Property(property="discount", type="number", format="float", example=50.00),
     *             @OA\Property(property="vat", type="number", format="float", example=150.00),
     *             @OA\Property(property="total", type="number", format="float", example=1100.00),
     *             @OA\Property(property="comment", type="string", example="Delivered on schedule"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom_id","quantity","item_price"},
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="uom_id", type="integer", example=5),
     *                     @OA\Property(property="quantity", type="integer", example=10),
     *                     @OA\Property(property="item_price", type="number", example=250.5),
     *                     @OA\Property(property="vat", type="number", example=25.5),
     *                     @OA\Property(property="discount", type="number", example=10.0),
     *                     @OA\Property(property="gross_total", type="number", example=2555.0),
     *                     @OA\Property(property="net_total", type="number", example=2300.0),
     *                     @OA\Property(property="total", type="number", example=2400.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Delivery created successfully")
     * )
     */
    // public function store(AgentDeliveryHeaderRequest $request): JsonResponse
    // {
    //     try {
    //         $header = $this->service->store($request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Delivery created successfully',
    //             'data' => $header
    //         ], 201);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }
    public function store(AgentDeliveryHeaderRequest $request): JsonResponse
    {
        try {
            $header = $this->service->store($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Delivery created successfully',
                'data'    => $header
            ], 201);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/agent-delivery/{uuid}",
     *     tags={"Agent Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific delivery by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the delivery",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Delivery fetched successfully")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $current  = $this->service->findByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Delivery fetched successfully',
                'data'    => new AgentDeliveryHeaderResource($current),
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/agent-delivery/update/{uuid}",
     *     tags={"Agent Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update delivery header and details by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the delivery to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="warehouse_id", type="integer", example=112),
     *             @OA\Property(property="route_id", type="integer", example=33),
     *             @OA\Property(property="salesman_id", type="integer", example=44),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="comment", type="string", example="Updated after delivery check"),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="uom_id", type="integer", example=12),
     *                     @OA\Property(property="quantity", type="integer", example=25),
     *                     @OA\Property(property="item_price", type="number", example=120.75)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Delivery updated successfully")
     * )
     */
    public function update(AgentDeliveryHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $header = $this->service->updateByUuid($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery updated successfully',
                'data' => new AgentDeliveryHeaderResource($header)
            ], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/agent_transaction/agent-delivery/{uuid}",
     *     tags={"Agent Delivery Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete a delivery by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the delivery to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Delivery deleted successfully")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'message' => 'Delivery deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // public function export()
    // {
    //     try {
    //         $timestamp = now()->format('Ymd_His');
    //         $fileName = "agent_delivery_export_{$timestamp}.csv";
    //         $filePath = storage_path("app/public/exports/{$fileName}");

    //         // Ensure the directory exists
    //         if (!file_exists(storage_path('app/public/exports'))) {
    //             mkdir(storage_path('app/public/exports'), 0777, true);
    //         }

    //         // Fetch your Agent Delivery data (adjust fields as needed)
    //         $deliveries = AgentDeliveryHeaders::select(
    //             'uuid',
    //             'delivery_date',
    //             'route_id',
    //             'status',
    //         )->get();

    //         // Define CSV headers
    //         $columns = [
    //             'UUID',
    //             'Delivery No',
    //             'Delivery Date',
    //             'Agent ID',
    //             'Route ID',
    //             'Status',
    //             'Created By',
    //             'Updated By'
    //         ];

    //         // Create & write CSV file
    //         $handle = fopen($filePath, 'w');
    //         fputcsv($handle, $columns);

    //         foreach ($deliveries as $row) {
    //             fputcsv($handle, [
    //                 $row->uuid,
    //                 $row->delivery_no,
    //                 $row->delivery_date,
    //                 $row->agent_id,
    //                 $row->route_id,
    //                 $row->status,
    //                 $row->created_by,
    //                 $row->updated_by
    //             ]);
    //         }

    //         fclose($handle);

    //         // ✅ Return direct download
    //         return response()->download($filePath, $fileName, [
    //             'Content-Type' => 'text/csv',
    //             'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    // public function export(Request $request)
    // {
    //     try {
    //         $timestamp = now()->format('Ymd_His');
    //         $fileName = "agent_delivery_export_{$timestamp}.csv";
    //         $filePath = "public/exports/{$fileName}";

    //         // Ensure export directory exists
    //         if (!Storage::exists('public/exports')) {
    //             Storage::makeDirectory('public/exports');
    //         }

    //         // Optional filters
    //         $status = $request->input('status');
    //         $startDate = $request->input('start_date');
    //         $endDate = $request->input('end_date');

    //         // Fetch header + details data
    //         $query = DB::table('agent_delivery_headers as h')
    //             ->leftJoin('agent_delivery_details as d', 'h.id', '=', 'd.header_id')
    //             ->select(
    //                 'h.delivery_code',
    //                 'h.delivery_date',
    //                 'h.customer_id',
    //                 'h.route_id',
    //                 'h.salesman_id',
    //                 'h.gross_total as header_gross_total',
    //                 'h.vat as header_vat',
    //                 'h.discount as header_discount',
    //                 'h.net_amount as header_net_amount',
    //                 'h.total as header_total',
    //                 'h.status as header_status',
    //                 'd.item_id',
    //                 'd.uom_id',
    //                 'd.item_price',
    //                 'd.quantity',
    //                 'd.vat as detail_vat',
    //                 'd.discount as detail_discount',
    //                 'd.gross_total as detail_gross_total',
    //                 'd.net_total as detail_net_total',
    //                 'd.total as detail_total'
    //             )
    //             ->orderBy('h.id', 'asc');

    //         // Apply filters if provided
    //         if ($status !== null) {
    //             $query->where('h.status', $status);
    //         }
    //         if ($startDate && $endDate) {
    //             $query->whereBetween('h.delivery_date', [$startDate, $endDate]);
    //         }

    //         $records = $query->get();

    //         // Create and open CSV file
    //         $handle = fopen(storage_path("app/{$filePath}"), 'w');

    //         // Define CSV headings
    //         $columns = [
    //             'Delivery Code',
    //             'Delivery Date',
    //             'Customer ID',
    //             'Route ID',
    //             'Salesman ID',
    //             'Header Gross Total',
    //             'Header VAT',
    //             'Header Discount',
    //             'Header Net Amount',
    //             'Header Total',
    //             'Header Status',
    //             'Item ID',
    //             'UOM ID',
    //             'Item Price',
    //             'Quantity',
    //             'Detail VAT',
    //             'Detail Discount',
    //             'Detail Gross Total',
    //             'Detail Net Total',
    //             'Detail Total'
    //         ];

    //         // Write header
    //         fputcsv($handle, $columns);

    //         // Write data rows
    //         foreach ($records as $row) {
    //             fputcsv($handle, [
    //                 $row->delivery_code,
    //                 $row->delivery_date,
    //                 $row->customer_id,
    //                 $row->route_id,
    //                 $row->salesman_id,
    //                 $row->header_gross_total,
    //                 $row->header_vat,
    //                 $row->header_discount,
    //                 $row->header_net_amount,
    //                 $row->header_total,
    //                 $row->header_status,
    //                 $row->item_id,
    //                 $row->uom_id,
    //                 $row->item_price,
    //                 $row->quantity,
    //                 $row->detail_vat,
    //                 $row->detail_discount,
    //                 $row->detail_gross_total,
    //                 $row->detail_net_total,
    //                 $row->detail_total
    //             ]);
    //         }

    //         fclose($handle);

    //         // Generate file public URL
    //         $publicUrl = asset("storage/exports/{$fileName}");

    //         // Return response
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Agent Delivery data exported successfully.',
    //             'url' => $publicUrl
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Export failed: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function exportHeaders(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Agent_delivery_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'deliveryheaderxports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);

        // SAME LOCATION HELPER
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new DeliveryHeaderExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );

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


    public function exportCapsCollectionfull(Request $request)
    {
        $uuid   = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));

        $extension = $format === 'csv' ? 'csv' : ($format === 'pdf' ? 'pdf' : 'xlsx');
        $deliveryHerader = AgentDeliveryHeaders::select('delivery_code')
            ->where('uuid', $uuid)
            ->first();

        $code = $deliveryHerader?->delivery_code ?? 'UNKNOWN';
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code);

        $filename = 'Agent_delivery_' . $code . '.' . $extension;
        $path = 'exports/' . $filename;
        // $filename  = 'agent_delivery_export_' . now()->format('Ymd_His') . '.' . $extension;
        // $path      = 'exports/' . $filename;
        if ($format === 'csv' || $format === 'xlsx') {

            $export = new DeliveryFulllExport($uuid);

            if ($format === 'csv') {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
            } else {
                Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
            }
        }
        if ($format === 'pdf') {

            $delivery = AgentDeliveryHeaders::with([
                'warehouse',
                'customer',
                'salesman',
                'route',
                'details.item',
                'details.Uom'
            ])->where('uuid', $uuid)->first();

            if (!$delivery) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Delivery not found.',
                ]);
            }

            $deliveryDetails = $delivery->details;
            $pdf = \PDF::loadView('delivery', [
                'delivery'         => $delivery,
                'deliveryDetails'  => $deliveryDetails
            ])->setPaper('A4');

            \Storage::disk('public')->makeDirectory('exports');
            \Storage::disk('public')->put($path, $pdf->output());
        }
        $appUrl  = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json([
            'status'       => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportDeliveryCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Delivery_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'deliveryexports/' . $filename;

        $filters = $request->input('filter', []);
        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;
        $routeIds = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        //    dd($filters);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new DeliveryCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // dd($export);

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
}
