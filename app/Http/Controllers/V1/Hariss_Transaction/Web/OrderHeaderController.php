<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\OrderHeaderResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\OrderListResource;
use App\Services\V1\Hariss_Transaction\Web\OrderHeaderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\HtOrderFullExport;
use App\Exports\HtOrderCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use App\Models\Hariss_Transaction\Web\HTOrderDetail;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CommonLocationFilter;

class OrderHeaderController extends Controller
{
    protected $service;

    public function __construct(OrderHeaderService $service)
    {
        $this->service = $service;
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/hariss_transaction/po_orders/list",
    //  *     tags={"PO Orders"},
    //  *     summary="Get paginated list of PO Orders with filters",
    //  *     security={{"bearerAuth":{}}},
    //  *
    //  *     @OA\Parameter(name="customer_id", in="query", description="Filter by customer ID", @OA\Schema(type="integer")),
    //  *     @OA\Parameter(name="salesman_id", in="query", description="Filter by salesman ID", @OA\Schema(type="integer")),
    //  *     @OA\Parameter(name="order_code", in="query", description="Search by order code", @OA\Schema(type="string")),
    //  *     @OA\Parameter(name="from_date", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
    //  *     @OA\Parameter(name="to_date", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
    //  *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="integer")),
    //  *     @OA\Parameter(name="limit", in="query", description="Items per page", @OA\Schema(type="integer", default=20)),
    //  *     @OA\Parameter(name="dropdown", in="query", description="Return dropdown format", @OA\Schema(type="boolean")),
    //  *
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="List of PO Orders",
    //  *         @OA\JsonContent(
    //  *              @OA\Property(property="status", type="string", example="success"),
    //  *              @OA\Property(property="code", type="integer", example=200),
    //  *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
    //  *              @OA\Property(property="pagination", type="object")
    //  *          )
    //  *     )
    //  * )
    //  */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = $request->except(['limit', 'dropdown']);

            $orders = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $orders
                ]);
            }

            $pagination = [
                'page'         => $orders->currentPage(),
                'limit'        => $orders->perPage(),
                'totalPages'   => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => OrderListResource::collection($orders),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // /**
    //  * @OA\Get(
    //  *     path="/api/hariss_transaction/po_orders/{uuid}",
    //  *     tags={"PO Orders"},
    //  *     summary="Get PO Order by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="PO Order UUID",
    //  *         @OA\Schema(type="string", format="uuid")
    //  *     ),
    //  *
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="PO Order details",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="data", type="object")
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=404, description="PO Order not found")
    //  * )
    //  */
    public function show(string $uuid): JsonResponse
    {
        try {
            $order = $this->service->getByUuid($uuid);

            if (!$order) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new OrderHeaderResource($order)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportHtOrderHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? now()->toDateString();
        $toDate   = $filters['to_date'] ?? now()->toDateString();

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $filename = 'Order_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'htorderexports/' . $filename; // path unchanged

        $export = new HtOrderFullExport(
            $fromDate,
            $toDate,
            $warehouseIds,
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
            'status'      => 'success',
            'download_url' => $fullUrl,
        ]);
    }

    public function exportOrderCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $filename = 'Order_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'htorderexports/' . $filename; // path unchanged

        $export = new HtOrderCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );

        // $export = new HtOrderCollapseExport($fromDate, $toDate);
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

    public function exportHtOrders(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';

        $filename = 'htorder_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;

        if ($extension === 'pdf') {
            $order = HTOrderHeader::with(['warehouse', 'customer'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $orderDetails = HTOrderDetail::with(['item', 'uoms'])
                ->where('header_id', $order->id)
                ->get();

            $pdf = \PDF::loadView('htorder', [
                'order'         => $order,
                'orderDetails'  => $orderDetails
            ]);
            Storage::disk('public')->put($path, $pdf->output());
            $appUrl = rtrim(config('app.url'), '/');
            $fullUrl = $appUrl . '/storage/app/public/' . $path;

            return response()->json([
                'status'        => 'success',
                'download_url'  => $fullUrl
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unsupported export format.'
        ], 400);
    }


    public function globalFilter(Request $request): JsonResponse
    {
        try {

            $perPage = $request->get('limit', 50);

            $filters = $request->except(['limit']);

            $orders = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'page'         => $orders->currentPage(),
                'limit'        => $orders->perPage(),
                'totalPages'   => $orders->lastPage(),
                'totalRecords' => $orders->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => OrderListResource::collection($orders),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
