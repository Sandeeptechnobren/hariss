<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\CapsHeaderResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\CapsHResource;
use App\Http\Requests\V1\Hariss_Transaction\Web\CapsDRequest;
use App\Services\V1\Hariss_Transaction\Web\CapsHService;
use App\Models\Hariss_Transaction\Web\HtCapsHeader;
use App\Models\Hariss_Transaction\Web\HtCapsDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\HTCapsHeaderExport;
use App\Exports\HTCapsCollapseExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;
use App\Helpers\LogHelper;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;

class CapsHController extends Controller
{
    protected $service;

    public function __construct(CapsHService $service)
    {
        $this->service = $service;
    }

    public function store(CapsDRequest $request)
    {
        $caps = $this->service->createCaps($request->validated());
        if ($caps) {
            LogHelper::store(
                '17',
                '104',
                'add',
                null,
                $caps->getAttributes(),
                auth()->id()
            );
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Caps created successfully',
            'data' => $caps,
        ], 201);
    }

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
                'currentPage'    => $orders->currentPage(),
                'perPage'        => $orders->perPage(),
                'lastPage'       => $orders->lastPage(),
                'total'          => $orders->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => CapsHResource::collection($orders),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve caps',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $order = $this->service->getByUuid($uuid);

            if (!$order) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'PO Order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new CapsHeaderResource($order)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve caps',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportHtCapsHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Caps_Deposit_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'htcapsexports/' . $filename;

        $filters = $request->input('filter', []);

        $fromDate = $filters['from_date'] ?? Null;
        $toDate   = $filters['to_date'] ?? Null;

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new HTCapsHeaderExport(
            $fromDate,
            $toDate,
            $warehouseIds
        );

        // $export = new HTCapsHeaderExport($fromDate, $toDate);

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

public function exportcapsCollapse(Request $request)
{
    $format = strtolower($request->input('format', 'xlsx'));

    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $writerType = $format === 'csv'
        ? \Maatwebsite\Excel\Excel::CSV
        : \Maatwebsite\Excel\Excel::XLSX;

    $filename = 'ht_caps_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'capscollapseexports/' . $filename;

    $filters = $request->input('filter', []);

    $fromDate     = $filters['from_date'] ?? null;
    $toDate       = $filters['to_date'] ?? null;
    $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

    $export = new HTCapsCollapseExport(
        $fromDate,
        $toDate,
        $warehouseIds,
    );

    Excel::store($export, $path, 'public', $writerType);

    $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

    return response()->json([
        'status'       => 'success',
        'download_url' => $fullUrl,
    ]);
}

    public function exportHtCaps(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';

        $filename = 'htcaps_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;

        if ($extension === 'pdf') {
            $order = HtCapsHeader::with(['warehouse', 'driverinfo'])
                ->where('uuid', $uuid)
                ->firstOrFail();
            $orderDetails = HtCapsDetail::with(['item', 'itemuom'])
                ->where('header_id', $order->id)
                ->get();

            $pdf = \PDF::loadView('htcaps', [
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

    public function update(Request $request, string $uuid)
    {
        $request->validate([
            'truck_no'   => 'nullable|string',
            'contact_no' => 'nullable|string',
            'details'    => 'nullable|array',
            'details.*.item_id'   => 'nullable|integer',
            'details.*.uom_id'    => 'nullable|integer',
            'details.*.quantity'  => 'nullable|numeric|min:1',
            'details.*.remarks'   => 'nullable|string',
            'details.*.remarks2'  => 'nullable|string',
        ]);

        $caps = $this->service->updateCapsByUuid($uuid, $request->all());

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Caps updated successfully',
            'data'    => $caps
        ]);
    }


    public function globalFilter(Request $request): JsonResponse
    {
        try {

            $perPage  = $request->get('limit', 20);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters  = $request->except(['limit', 'dropdown']);

            $data = $this->service->globalFilter($perPage, $filters);

            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $data
                ]);
            }

            $pagination = [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total()
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => CapsHResource::collection($data),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve invoices',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
