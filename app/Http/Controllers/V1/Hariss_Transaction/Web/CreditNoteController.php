<?php

namespace App\Http\Controllers\V1\Hariss_Transaction\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Hariss_Transaction\Web\StoreCreditNoteRequest;
use App\Http\Resources\V1\Hariss_Transaction\Web\CreditNoteResource;
use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use App\Services\V1\Hariss_Transaction\Web\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CreditNoteHeaderExport;
use App\Exports\CreditNoteCollapseExport;
use App\Exports\CreditNotedistributorListExport;
use App\Models\Hariss_Transaction\Web\HtReturnHeader;

class CreditNoteController extends Controller
{
    protected $service;

    public function __construct(CreditNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $result = $this->service->list($request);

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function store(StoreCreditNoteRequest $request)
    {
        $data = $this->service->create($request->validated());

        return new CreditNoteResource($data);
    }
    public function show($uuid)
    {
        $creditNote = CreditNoteHeader::where('uuid', $uuid)
            ->with(['details', 'supplier', 'purchaseInvoice'])
            ->firstOrFail();

        return new CreditNoteResource($creditNote);
    }
    public function destroy(CreditNoteHeader $creditNote)
    {
        $creditNote->delete();

        return response()->json([
            'status' => true,
            'message' => 'Credit Note Deleted Successfully'
        ]);
    }

public function list(Request $request)
{
    $query = CreditNoteHeader::with([
        'customer:id,business_name,osa_code',
        'salesman:id,name',
        'distributor:id,uuid,warehouse_name,warehouse_code',
        'purchaseInvoice:id,invoice_code'
    ]);
    // ✅ Filter by distributor UUID
    if ($request->filled('distributor_uuid')) {
        $uuid = trim($request->distributor_uuid);
        // Step 1: distributor find karo
        $distributor = \App\Models\Warehouse::where('uuid', $uuid)->first();
        // Step 2: agar mila to filter lagao
        if ($distributor) {
            $query->where('distributor_id', $distributor->id);
        } else {
            // agar UUID galat hai to empty data return karo
            return response()->json([
                'status' => true,
                'code' => 200,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 50,
                    'totalPages' => 0,
                    'totalRecords' => 0,
                ]
            ]);
        }
    }
    $data = $query->orderBy('id', 'asc')->paginate(50);
    return response()->json([
        'status' => 'success',
        'code' => 200,
        'data' => collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'credit_note_no' => $item->credit_note_no,
                'purchase_invoice' => $item->purchaseInvoice ? [
                    'id' => $item->purchaseInvoice->id,
                    'invoice_code' => $item->purchaseInvoice->invoice_code
                ] : null,
                'supplier_id' => $item->supplier_id,
                'total_amount' => $item->total_amount,
                'reason' => $item->reason,
                'status' => $item->status,
                'customer' => $item->customer ? [
                    'id' => $item->customer->id,
                    'code' => $item->customer->osa_code,
                    'name' => $item->customer->business_name
                ] : null,
                'salesman' => $item->salesman ? [
                    'id' => $item->salesman->id,
                    'name' => $item->salesman->name
                ] : null,
                'distributor' => $item->distributor ? [
                    'id' => $item->distributor->id,
                    'uuid' => $item->distributor->uuid,
                    'code' => $item->distributor->warehouse_code,
                    'name' => $item->distributor->warehouse_name
                ] : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }),
        'pagination' => [
            'page' => $data->currentPage(),
            'limit' => $data->perPage(),
            'totalPages' => $data->lastPage(),
            'totalRecords' => $data->total(),
        ]
    ]);
}
public function exportList(Request $request)
{
    $filename = 'CreditNotedistributorList' . time() . '.xlsx';
    $path = 'exports/' . $filename;

    Excel::store(new CreditNotedistributorListExport($request), $path, 'public');

    return response()->json([
        'status' => true,
        'download_url' => asset('storage/' . $path)
    ]);
}
public function distributorglobalFilter(Request $request)
{
    $query = CreditNoteHeader::with([
        'customer:id,business_name,osa_code',
        'salesman:id,name',
        'distributor:id,uuid,warehouse_name,warehouse_code',
        'purchaseInvoice:id,invoice_code'
    ]);

    // 🔥 FILTER ARRAY
    $filter = $request->input('filter', []);

    $fromDate = $filter['from_date'] ?? null;
    $toDate   = $filter['to_date'] ?? null;
    $uuid     = $filter['distributor_uuid'] ?? null;

    // ✅ DATE FILTER
    if ($fromDate && $toDate) {
        $query->whereDate('created_at', '>=', $fromDate)
              ->whereDate('created_at', '<=', $toDate);
    }

    // ✅ DISTRIBUTOR UUID FILTER (BEST WAY)
    if (!empty($uuid)) {
        $uuid = trim($uuid);

        $query->whereHas('distributor', function ($q) use ($uuid) {
            $q->where('uuid', $uuid);
        });
    }

    $data = $query->orderBy('id', 'asc')->paginate(50);

    return response()->json([
        'status' => 'success',
        'code' => 200,
        'data' => collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'credit_note_no' => $item->credit_note_no,

                'purchase_invoice' => $item->purchaseInvoice ? [
                    'id' => $item->purchaseInvoice->id,
                    'invoice_code' => $item->purchaseInvoice->invoice_code
                ] : null,

                'supplier_id' => $item->supplier_id,
                'total_amount' => $item->total_amount,
                'reason' => $item->reason,
                'status' => $item->status,

                'customer' => $item->customer ? [
                    'id' => $item->customer->id,
                    'code' => $item->customer->osa_code,
                    'name' => $item->customer->business_name
                ] : null,

                'salesman' => $item->salesman ? [
                    'id' => $item->salesman->id,
                    'name' => $item->salesman->name
                ] : null,

                'distributor' => $item->distributor ? [
                    'id' => $item->distributor->id,
                    'uuid' => $item->distributor->uuid,
                    'code' => $item->distributor->warehouse_code,
                    'name' => $item->distributor->warehouse_name
                ] : null,

                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }),

        'pagination' => [
            'page' => $data->currentPage(),
            'limit' => $data->perPage(),
            'totalPages' => $data->lastPage(),
            'totalRecords' => $data->total(),
        ]
    ]);
}

public function exportHeader(Request $request)
{
    $filename = 'credit_note_header_' . time() . '.xlsx';
    $path = 'exports/' . $filename;

    Excel::store(new CreditNoteHeaderExport($request), $path, 'public');

    return response()->json([
        'status' => true,
        'download_url' => asset('storage/' . $path)
    ]);
}

public function exportCollapse(Request $request)
{
    $filename = 'credit_note_collapse_' . time() . '.xlsx';
    $path = 'exports/' . $filename;

    Excel::store(new CreditNoteCollapseExport($request), $path, 'public');

    return response()->json([
        'status' => true,
        'download_url' => asset('storage/' . $path)
    ]);
}

public function dropdown()
{
    try {

        $data = \DB::table('ht_return_header as h')
            ->leftJoin('credit_note_headers as c', 'h.id', '=', 'c.purchase_return_id')
            ->whereNull('c.purchase_return_id') 
            ->select(
                'h.id',
                'h.return_code as code',
                'h.uuid'
            )
            ->orderBy('h.id', 'desc')
            ->limit(100) 
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Dropdown fetched successfully',
            'data' => $data
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function getCreditNoteFullByInvoiceUuid($uuid)
{
    try {

        $data = HtReturnHeader::with([
            'customer:id,business_name,osa_code',
           // 'salesman:id,name',
            'distributor',
            'details:id,header_id,item_id,qty,item_value,total,net,vat'
        ])
        ->where('uuid', $uuid)
        ->first();

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'No data found'
            ], 200);
        }

        $warehouse = $data->distributor;

        $finalData = [
            'id' => $data->id,
            'uuid' => $data->uuid,
            'return_code' => $data->return_code,

            'sap_id' => $data->sap_id,
            'total_amount' => $data->total,
            'status' => $data->status,
            'total_net' => $data->net,
            'total_vat' => $data->vat,

            'customer' => $data->customer ? [
                'id' => $data->customer->id,
                'code' => $data->customer->osa_code,
                'name' => $data->customer->business_name
            ] : null,

            // 'salesman' => $data->salesman ? [
            //     'id' => $data->salesman->id,
            //     'name' => $data->salesman->name
            // ] : null,

            'distributor' => $warehouse ? [
                'id' => $data->warehouse_id,
                'uuid' => $warehouse->uuid,
                'code' => $warehouse->warehouse_code,
                'name' => $warehouse->warehouse_name
            ] : [
                'id' => $data->warehouse_id,
                'uuid' => null,
                'code' => null,
                'name' => null
            ],
           
            // 🔥 DETAILS ADD
            'details' => $data->details->map(function ($d) {
    return [
        'item' => $d->item ? [
            'item_id'=>$d->item->id,
            'erp_code' => $d->item->erp_code,
            'name' => $d->item->name
        ] : null,
          //  'uom_id'     => $d->uom_id,
            'qty'   => $d->qty,
            'item_value' => $d->item_value,
            'total'      => $d->total,
            'net'        => $d->net,
            'vat'        =>$d->vat,

        ];
        }),

            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Invoice data fetched successfully',
            'data' => $finalData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function globalFilter(Request $request)
{
    try {
        $result = $this->service->globalFilter($request);

        return response()->json($result);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
}