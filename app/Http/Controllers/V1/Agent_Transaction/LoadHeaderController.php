<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\LoadHeaderRequest;
use App\Http\Requests\V1\Agent_Transaction\LoadHeaderUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\LoadHeaderResource;
use App\Services\V1\Agent_Transaction\LoadHeaderService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoadHeaderFullExport;
use App\Exports\LoadFullExport;
use App\Exports\LoadCollapseExport;
use Carbon\Carbon;
use App\Helpers\LogHelper;
use App\Helpers\CommonLocationFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\LoadDetail;


/**
 * @OA\Tag(
 *     name="Salesman Load Header",
 *     description="API endpoints for managing Load Headers and their Details"
 * )
 */
class LoadHeaderController extends Controller
{
    public function __construct(protected LoadHeaderService $service) {}

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load/list",
     *     tags={"Salesman Load Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="List all loads",
     *     @OA\Response(
     *         response=200,
     *         description="List of all loads"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            $perPage = $request->get('per_page', 50);
            $headers = $this->service->all($perPage, $filters);
            return ResponseHelper::paginatedResponse(
                'Loads fetched successfully',
                LoadHeaderResource::class,
                $headers
            );
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function globalFilter(Request $request): JsonResponse
    {
        try {
            // ✅ pagination
            $perPage = $request->get('per_page', 50);

            // ✅ pass full payload (including filter object)
            $filters = $request->all();

            // ✅ call global filter
            $loads = $this->service->globalFilter($perPage, $filters);

            // ✅ SAME response helper (unchanged)
            return ResponseHelper::paginatedResponse(
                'Loads fetched successfully',
                LoadHeaderResource::class,
                $loads
            );
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/agent_transaction/load/add",
     *     tags={"Salesman Load Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a new Load with details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"warehouse_id","route_id","salesman_id","details"},
     *             @OA\Property(property="warehouse_id", type="integer", example=112),
     *             @OA\Property(property="route_id", type="integer", example=60),
     *             @OA\Property(property="salesman_id", type="integer", example=133),
     *             @OA\Property(property="is_confirmed", type="integer", example=1, description="1 = Pending, 2 = Waiting, 3 = Approved"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"item_id","uom","qty","price","status"},
     *                     @OA\Property(property="item_id", type="integer", example=77),
     *                     @OA\Property(property="uom", type="integer", example=27),
     *                     @OA\Property(property="qty", type="integer", example=25),
     *                     @OA\Property(property="price", type="number", format="float", example=250.5),
     *                     @OA\Property(property="status", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Load created successfully")
     * )
     */
    public function store(LoadHeaderRequest $request): JsonResponse
    {
        try {
            $result = $this->service->store($request->all());

            if (isset($result['status']) && $result['status'] === 'error') {
                // Return the error AS IS → Do NOT wrap in resource
                return response()->json($result, $result['code'] ?? 400);
            }
            if ($result) {
                LogHelper::store(
                    '13',
                    '40',
                    'add',
                    null,
                    $result->getAttributes(),
                    auth()->id()
                );
            }
            return (new LoadHeaderResource($result))
                ->additional([
                    'status' => 'success',
                    'message' => 'Load created successfully'
                ])
                ->response()
                ->setStatusCode(200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load/{uuid}",
     *     tags={"Salesman Load Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get a specific load by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the load",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Load fetched successfully")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $header = $this->service->findByUuid($uuid);
            return response()->json([
                'status' => 'success',
                'data' => new LoadHeaderResource($header)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/agent_transaction/load/update/{uuid}",
     *     tags={"Salesman Load Header"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update Load header and details by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the load to update",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="warehouse_id", type="integer", example=3),
     *             @OA\Property(property="route_id", type="integer", example=12),
     *             @OA\Property(property="salesman_id", type="integer", example=8),
     *             @OA\Property(property="is_confirmed", type="boolean", example=true),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="details",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="item_id", type="integer", example=10),
     *                     @OA\Property(property="uom", type="string", example="BOX"),
     *                     @OA\Property(property="qty", type="integer", example=50),
     *                     @OA\Property(property="price", type="number", example=150.25),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Load updated successfully")
     * )
     */
    public function update(LoadHeaderUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $header = $this->service->updateByUuid($uuid, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Load updated successfully',
                'data' => new LoadHeaderResource($header)
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/agent_transaction/load/{uuid}",
    //  *     tags={"Salesman Load Header"},
    //  *     security={{"bearerAuth":{}}},
    //  *     summary="Delete a Load by UUID",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the load to delete",
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\Response(response=200, description="Load deleted successfully")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     try {
    //         $this->service->deleteByUuid($uuid);
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Load deleted successfully'
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load/export",
     *     summary="Export Load Headers",
     *     description="Exports Load Header",
     *     tags={"Load Header"},
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Export format (xlsx or csv)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"xlsx", "csv"},
     *             default="xlsx"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found or no data available",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Load not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function exportLoadHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Load_header_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'loadexports/' . $filename;


        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new LoadHeaderFullExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $routeIds,
            $salesmanIds
        );
        // $export = new LoadHeaderFullExport();

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

    public function exportLoad(Request $request)
    {
        $uuid   = $request->input('uuid');
        $format = strtolower($request->input('format', 'xlsx'));

        $extension = $format === 'csv'
            ? 'csv'
            : ($format === 'pdf' ? 'pdf' : 'xlsx');

        $header = LoadHeader::select('osa_code')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $osaCode = preg_replace('/[^A-Za-z0-9\-]/', '', $header->osa_code);

        $filename = 'Load_' . $osaCode . '.' . $extension;
        $path     = 'loadexports/' . $filename;

        if ($format === 'pdf') {

            $order = LoadHeader::with([
                'warehouse',
                'salesman.subtype',
                'salesman.salesmanType'
            ])->where('uuid', $uuid)->firstOrFail();

            $orderDetails = LoadDetail::with([
                'item',
                'Uom'
            ])->where('header_id', $order->id)->get();

            $pdf = Pdf::loadView('load', [
                'order'        => $order,
                'orderDetails' => $orderDetails,
            ])->setPaper('A4', 'portrait');

            \Storage::disk('public')->put($path, $pdf->output());

            $appUrl  = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;

            return response()->json([
                'status'       => 'success',
                'download_url' => $fullUrl,
            ]);
        }
        $export = new LoadFullExport($uuid);

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

    public function exportCollapseLoad(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Load_detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'loadexports/' . $filename;


        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        $routeIds    = CommonLocationFilter::normalizeIds($filters['route_id'] ?? []);
        $salesmanIds = CommonLocationFilter::normalizeIds($filters['salesman_id'] ?? []);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new LoadCollapseExport(
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

    /**
     * @OA\Get(
     *     path="/api/agent_transaction/load/warehouse/{warehouse_id}/stock",
     *     summary="Get stock items for a specific warehouse",
     *     description="Warehouse ID ke base par warehouse stock, item name, item code aur quantity return karta hai.",
     *     tags={"Salesman Load Header"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="path",
     *         required=true,
     *         description="Warehouse ID",
     *         @OA\Schema(type="integer")
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="warehouse_id", type="integer", example=5),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="item_id", type="integer", example=12),
     *                     @OA\Property(property="item_name", type="string", example="Pencil"),
     *                     @OA\Property(property="item_code", type="string", example="PNC-001"),
     *                     @OA\Property(property="quantity", type="integer", example=50)
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=404,
     *         description="Warehouse not found"
     *     )
     * )
     */
    public function getWarehouseStock($warehouse_id)
    {
        // Validate warehouse_id
        $validated = validator(
            ['warehouse_id' => $warehouse_id],
            ['warehouse_id' => 'required|integer|exists:tbl_warehouse,id']
        )->validate();

        // Define the number of items per page (can be dynamic, e.g., $request->get('per_page', 50))
        $perPage = 50;

        // Fetch stocks with pagination
        $stocksPaginator = DB::table('tbl_warehouse_stocks as ws')
            ->join('items as i', 'i.id', '=', 'ws.item_id')
            ->where('ws.warehouse_id', $warehouse_id)
            ->select(
                'ws.item_id',
                'i.name as item_name',
                'i.erp_code as ERP_Code',
                'i.code as item_code',
                'ws.qty'
            )
            // Use paginate() to get the paginator object
            ->paginate($perPage);

        // Manually extract and structure the response data
        return response()->json([
            'status' => 'success', // Updated status string
            'code' => 200,          // Added status code
            'message' => 'Stocks fetched successfully', // Updated message
            'data' => $stocksPaginator->items(), // Get only the items array for the 'data' key
            'pagination' => [
                'current_page' => $stocksPaginator->currentPage(),
                'last_page' => $stocksPaginator->lastPage(),
                'per_page' => $stocksPaginator->perPage(),
                'total' => $stocksPaginator->total()
                // You can add more keys like 'next_page_url', 'prev_page_url', etc., if needed
            ]
        ]);
    }

    public function getSalesmanRouteByWarehouse(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer'
        ]);

        $warehouseId = $request->warehouse_id;

        $data = DB::table('tbl_salesman_warehouse_history as swh')
            ->leftJoin('salesman as s', 's.id', '=', 'swh.salesman_id')
            ->leftJoin('tbl_route as r', 'r.id', '=', 'swh.route_id')
            ->where('swh.warehouse_id', $warehouseId)
            ->whereDate('swh.requested_date', Carbon::today())
            ->selectRaw('DISTINCT ON (swh.salesman_id) 
                swh.salesman_id,
                swh.route_id,
                s.osa_code as salesman_code,
                s.name as salesman_name,
                r.route_code,
                r.route_name
            ')
            ->orderBy('swh.salesman_id')
            ->orderByDesc('swh.requested_date')
            ->get();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Today salesman & route fetched successfully',
            'data'    => $data
        ]);
    }


    public function getSalesmanRoutes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|integer',
            'warehouse_id'     => 'required|integer',
        ]);

        $salesmen = $this->service->getSalesmanWithRoutes(
            $validated['type'],
            $validated['warehouse_id']
        );

        return response()->json([
            'status' => 'success',
            'data'   => $salesmen
        ]);
    }
}
