<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\DeliveryHeaderResource;
use App\Services\V1\Hariss_Transaction\Web\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\HtDeliveryFullExport;
use App\Exports\HtDeliveryCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use App\Models\Hariss_Transaction\Web\HTDeliveryDetail;
use Illuminate\Support\Facades\Storage;
use App\Helpers\CommonLocationFilter;

class DeliveryController extends Controller
{
    protected $service;

    public function __construct(DeliveryService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/ht_delivery/list",
     *     tags={"Hariss Delivery"},
     *     summary="Get paginated list of Delivery Orders with filters",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="customer_id", in="query", description="Filter by customer ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="salesman_id", in="query", description="Filter by salesman ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="delivery_code", in="query", description="Search by delivery code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="from_date", in="query", description="Filter from date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to_date", in="query", description="Filter to date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", description="Items per page", @OA\Schema(type="integer", default=20)),
     *     @OA\Parameter(name="dropdown", in="query", description="Return dropdown format", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of Delivery Orders",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="pagination", type="object")
     *          )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $perPage  = $request->get('pageSize', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters  = $request->except([
                'page',
                'pageSize',
                'dropdown'
            ]);

            $deliveries = $this->service->getAll($perPage, $filters, $dropdown);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $deliveries
                ]);
            }

            $pagination = [
                'page'         => $deliveries->currentPage(),
                'limit'        => $deliveries->perPage(),
                'totalPages'   => $deliveries->lastPage(),
                'totalRecords' => $deliveries->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => DeliveryHeaderResource::collection($deliveries),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve delivery orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // public function index(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage  = $request->get('limit', 50);
    //         $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

    //         $filters  = $request->except(['limit', 'dropdown']);

    //         $deliveries = $this->service->getAll($perPage, $filters, $dropdown);

    //         if ($dropdown) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'code'   => 200,
    //                 'data'   => $deliveries
    //             ]);
    //         }

    //         $pagination = [
    //             'page'         => $deliveries->currentPage(),
    //             'limit'        => $deliveries->perPage(),
    //             'totalPages'   => $deliveries->lastPage(),
    //             'totalRecords' => $deliveries->total(),
    //         ];

    //         return response()->json([
    //             'status'     => 'success',
    //             'code'       => 200,
    //             'data'       => DeliveryHeaderResource::collection($deliveries),
    //             'pagination' => $pagination
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Failed to retrieve delivery orders',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }


    /**
     * @OA\Get(
     *     path="/api/hariss_transaction/ht_delivery/{uuid}",
     *     tags={"Hariss Delivery"},
     *     summary="Get Delivery Order by UUID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Delivery Order UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Delivery Order details",
     *         @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="code", type="integer", example=200),
     *              @OA\Property(property="data", type="object")
     *          )
     *     ),
     *     @OA\Response(response=404, description="Delivery Order not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $delivery = $this->service->getByUuid($uuid);

            if (!$delivery) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Delivery not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new DeliveryHeaderResource($delivery)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve delivery order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportDeliveryHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Delivery_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'deliveryexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new HtDeliveryFullExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );

        // $export = new HtDeliveryFullExport($fromDate, $toDate);

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

    public function exportDeliveryCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'Delivery_Detail_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'deliveryexports/' . $filename;

        $filters = $request->input('filter', []);
        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $salesmanIds = !empty($filters['salesman_id'])
            ? explode(',', $filters['salesman_id'])
            : [];
// dd($filters);
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);
        $export = new HtDeliveryCollapseExport(
            $fromDate,
            $toDate,
            $warehouseIds,
            $salesmanIds
        );
        // $export = new HtDeliveryCollapseExport($fromDate, $toDate);

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

    public function exportHtDelivery(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';

        $filename = 'poorder_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;

        if ($extension === 'pdf') {
            $order = HTDeliveryHeader::with(['warehouse', 'customer'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $orderDetails = HTDeliveryDetail::with(['item', 'uoms'])
                ->where('header_id', $order->id)
                ->get();

            $pdf = \PDF::loadView('htdelivery', [
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

            $perPage  = $request->get('limit', 50);

            $filters  = $request->except(['limit']);

            $deliveries = $this->service->globalFilter($perPage, $filters);

            $pagination = [
                'page'         => $deliveries->currentPage(),
                'limit'        => $deliveries->perPage(),
                'totalPages'   => $deliveries->lastPage(),
                'totalRecords' => $deliveries->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => DeliveryHeaderResource::collection($deliveries),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve delivery orders',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
