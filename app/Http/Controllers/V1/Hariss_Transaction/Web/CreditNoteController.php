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

class CreditNoteController extends Controller
{
    protected $service;

    public function __construct(CreditNoteService $service)
    {
        $this->service = $service;
    }

    // public function index(Request $request)
    // {
    //     $result = $this->service->list($request);

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'data' => CreditNoteResource::collection($result->items()),
    //         'pagination' => [
    //             'page' => $result->currentPage(),
    //             'limit' => $result->perPage(),
    //             'totalPages' => $result->lastPage(),
    //             'totalRecords' => $result->total(),
    //         ]
    //     ]);
    // }
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

// public function list(Request $request)
// {
//     $data = CreditNoteHeader::with([
//         'customer:id,business_name,osa_code',
//         'salesman:id,name',
//         'distributor:id,warehouse_name,warehouse_code',
//         'purchaseInvoice:id,invoice_code'
//     ])
//     ->orderBy('id', 'asc')
//     ->paginate(50);

//     return response()->json([
//         'status' => 'success',
//         'code' => 200,
//         'data' => collect($data->items())->map(function ($item) {
//             return [
//                 'id' => $item->id,
//                 'uuid' => $item->uuid,
//                 'credit_note_no' => $item->credit_note_no,

//                 'purchase_invoice' => $item->purchaseInvoice ? [
//                     'id' => $item->purchaseInvoice->id,
//                     'invoice_code' => $item->purchaseInvoice->invoice_code
//                 ] : null,

//                 'supplier_id' => $item->supplier_id,
//                 'total_amount' => $item->total_amount,
//                 'reason' => $item->reason,
//                 'status' => $item->status,

//                 // ✅ customer with code
//                 'customer' => $item->customer ? [
//                     'id' => $item->customer->id,
//                     'code' => $item->customer->osa_code,
//                     'name' => $item->customer->business_name
//                 ] : null,

//                 'salesman' => $item->salesman ? [
//                     'id' => $item->salesman->id,
//                     'name' => $item->salesman->name
//                 ] : null,

//                 'distributor' => $item->distributor ? [
//                     'id' => $item->distributor->id,
//                     'code' => $item->distributor->warehouse_code,
//                     'name' => $item->distributor->warehouse_name
//                 ] : null,

//                 'created_at' => $item->created_at,
//                 'updated_at' => $item->updated_at,
//             ];
//         }),

//         'pagination' => [
//             'page' => $data->currentPage(),
//             'limit' => $data->perPage(),
//             'totalPages' => $data->lastPage(),
//             'totalRecords' => $data->total(),
//         ]
//     ]);
// }
 
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
}