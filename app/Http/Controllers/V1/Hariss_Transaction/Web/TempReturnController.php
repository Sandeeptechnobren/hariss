<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Hariss_Transaction\Web\TempReturnHResource;
use App\Http\Resources\V1\Hariss_Transaction\Web\TempReturnResource;
use App\Services\V1\Hariss_Transaction\Web\TempReturnService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Hariss_Transaction\Web\TempReturnH;
use App\Models\Hariss_Transaction\Web\TempReturnD;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TempReturnHExport;
use App\Exports\TempReturnCollapseExport;
use Illuminate\Support\Facades\DB;

class TempReturnController extends Controller
{
    protected $service;

    public function __construct(TempReturnService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage  = $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

            $filters = $request->except(['limit', 'dropdown']);

            $returns = $this->service->list($perPage, $filters, $dropdown);
            if ($dropdown) {
                return response()->json([
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $returns
                ]);
            }

            $pagination = [
                'page'         => $returns->currentPage(),
                'limit'        => $returns->perPage(),
                'totalPages'   => $returns->lastPage(),
                'totalRecords' => $returns->total(),
            ];

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'data'       => TempReturnHResource::collection($returns),
                'pagination' => $pagination
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return records',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, string $uuid)
    {
        try {
            $record = $this->service->viewByUuid($uuid);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Return record not found for the given UUID'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => new TempReturnResource($record)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve return record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportTempReturn(Request $request)
    {
        $uuid = $request->input('uuid');
        $format = strtolower($request->input('format', 'pdf'));
        $extension = in_array($format, ['pdf', 'csv', 'xlsx']) ? $format : 'pdf';

        $filename = 'tempreturn_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'exports/' . $filename;

        if ($extension === 'pdf') {

            $order = TempReturnH::with([
                'parent',
                'parent.warehouse',
                'customer'
            ])
                ->where('uuid', $uuid)
                ->firstOrFail();

            $orderDetails = TempReturnD::with(['item', 'uom'])
                ->where('header_id', $order->id)
                ->get();

            $order->vat   = $orderDetails->sum('vat');
            $order->net   = $orderDetails->sum('net');
            $order->total = $orderDetails->sum('total');

            $pdf = \PDF::loadView('tempreturn', [
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

    public function exportTempReturnHeader(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $filename = 'tempreturn_header_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'tempreturnexports/' . $filename;
        $export = new TempReturnHExport($fromDate, $toDate);

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

    public function exportTempReturnCollapse(Request $request)
    {
        $format = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $filename = 'ht_caps_collapse_export_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'tempreturncollapseexports/' . $filename;
        $export = new TempReturnCollapseExport($fromDate, $toDate);

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
    public function getTempReturnHeaders(Request $request)
    {
        $request->validate([
            'return_id' => 'required|integer'
        ]);

        $returnId = $request->return_id;
        $mainHeader = DB::table('ht_return_header')
            ->where('id', $returnId)
            ->whereNull('deleted_at')
            ->first();

        if (! $mainHeader) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Return header not found'
            ], 404);
        }
        $originalDetails = DB::table('ht_return_details as rd')
            ->leftJoin('items', 'items.id', '=', 'rd.item_id')
            ->leftJoin('uom', 'uom.id', '=', 'rd.uom')
            ->where('rd.header_id', $returnId)
            ->whereNull('rd.deleted_at')
            ->select(
                'rd.*',
                'items.name as item_name',
                'uom.name as uom_name'
            )
            ->get();

        $tempHeaders = DB::table('temp_return_header')
            ->where('parent_header_id', $returnId)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'uuid',
                'return_code',
                'invoice_sap_id',
                'customer_returnheader_sapid',
                'return_reason',
                'return_type',
                'vat',
                'net',
                'total',
                'sap_return_msg',
                'created_at'
            )
            ->get();

        $tempHeaderIds = $tempHeaders->pluck('id');

        $tempDetails = DB::table('temp_return_details as trd')
            ->leftJoin('items', 'items.id', '=', 'trd.item_id')
            ->leftJoin(
                'uom',
                DB::raw('uom.id'),
                '=',
                DB::raw('CAST(trd.uom AS INTEGER)')
            )
            ->whereIn('trd.header_id', $tempHeaderIds)
            ->whereNull('trd.deleted_at')
            ->select(
                'trd.*',
                'items.name as item_name',
                'uom.name as uom_name'
            )
            ->get()
            ->groupBy('header_id');
        $tempHeaders = $tempHeaders->map(function ($header) use ($tempDetails) {
            $header->details = $tempDetails[$header->id] ?? [];
            return $header;
        });
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Return and split temp return data fetched successfully',
            'data'    => [
                'return_header'   => $mainHeader,
                'return_details'  => $originalDetails,
                'temp_returns'   => $tempHeaders
            ]
        ], 200);
    }
}
