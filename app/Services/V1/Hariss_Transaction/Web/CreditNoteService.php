<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use App\Models\Hariss_Transaction\Web\CreditNoteDetail;
use Illuminate\Support\Facades\DB;
use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use App\Helpers\CommonLocationFilter;
use App\Helpers\JournalEntryHelper;

class CreditNoteService
{
    // public function create($data)
    // {
    //     return DB::transaction(function () use ($data) {
    //         $totalAmount = 0;
    //         // $invoice = HTInvoiceHeader::findOrFail($data['purchase_invoice_id']);
    //         $invoice = HtReturnHeader::findOrFail($data['purchase_return_id']);

    //          //dd($invoice);
    //         $creditNote = CreditNoteHeader::create([
    //             'credit_note_no' => $data['credit_note_no'],
    //             'purchase_return_id' => $invoice->id,
    //             'supplier_id' => $invoice->sap_id,  
    //             'customer_id' => $data['customer_id'] ?? null,
    //             'salesman_id' => $data['salesman_id'] ?? null,
    //             'distributor_id' => $data['distributor_id'] ?? null,
    //             'reason' => $data['reason'] ?? null,
    //             'status' => 1,  
    //             'total_amount' => 0
    //         ]);
    //         foreach ($data['details'] as $item) {
    //             $itemId = $item['item_id'];
    //             $qty    = $item['qty'];
    //             $price  = $item['price'];
    //             // $purchaseQty = HTInvoiceDetail::where('header_id', $invoice->id)
    //             //     ->where('item_id', $itemId)
    //             //      ->value('quantity');
    //             // if (!$purchaseQty) {
    //             //     throw new \Exception("Item not found in invoice: {$itemId}");
    //             // }
    //             // $returnedQty = CreditNoteDetail::where('purchase_invoice_id', $invoice->id)
    //             //     ->where('item_id', $itemId)
    //             //     ->sum('qty');
    //             // if (($returnedQty + $qty) > $purchaseQty) {
    //             //     throw new \Exception("Return exceeded for item {$itemId}");
    //             // }
    //             $purchaseQty = HtReturnDetail::where('header_id', $invoice->id)
    //                 ->where('item_id', $itemId)
    //                 ->sum('qty');
    //             if ($purchaseQty === null) {
    //                 throw new \Exception("Item not found in invoice: {$itemId}");
    //             }
    //             $returnedQty = CreditNoteDetail::where('credit_note_id', $invoice->id)
    //                 ->where('item_id', $itemId)
    //                 ->sum('qty');

    //             if (($returnedQty + $qty) > $purchaseQty) {
    //                 throw new \Exception("Return exceeded for item {$itemId}");
    //             }
    //             $lineTotal = $qty * $price;
    //             $totalAmount += $lineTotal;
    //             CreditNoteDetail::create([
    //                 'credit_note_id' => $creditNote->id,
    //                 'item_id' => $itemId,
    //                 'qty' => $qty,
    //                 'price' => $price,
    //                 'total' => $lineTotal,
    //             ]);    
    //         }
    //         $creditNote->update([
    //             'total_amount' => $totalAmount
    //         ]);
    //         createCreditNoteJournal($creditNote);
    //         return $creditNote->load(['details', 'supplier', 'purchaseInvoice']);
    //     });
    // }

    public function create($data)
{ 
    return DB::transaction(function () use ($data) {

        $totalAmount = 0;

        // ✅ Get Purchase Return
        $invoice = HtReturnHeader::findOrFail($data['purchase_return_id']);

        // ✅ Create Credit Note Header
        $creditNote = CreditNoteHeader::create([
            'credit_note_no'     => $data['credit_note_no'],
            'purchase_return_id' => $invoice->id,
            'supplier_id'        => $invoice->sap_id,
            'customer_id'        => $data['customer_id'] ?? null,
            'salesman_id'        => $data['salesman_id'] ?? null,
            'distributor_id'     => $data['distributor_id'] ?? null,
            'reason'             => $data['reason'] ?? null,
            'status'             => 1,
            'total_amount'       => 0
        ]);

        foreach ($data['details'] as $item) {
      
            $itemId  = $item['item_id'];
            $qty     = $item['qty'];
            $price   = $item['price'];
            $batchNo = $item['batch_no'];

            // ✅ Purchase return qty (batch wise)
            $purchaseQty = HtReturnDetail::where('header_id', $invoice->id)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo) // 🔥 IMPORTANT
                ->sum('qty');

            if ($purchaseQty <= 0) {
                throw new \Exception("Item or batch not found: {$itemId} / {$batchNo}");
            }

            // ✅ Already credited qty (batch wise)
            $returnedQty = CreditNoteDetail::whereHas('header', function ($q) use ($invoice) {
                    $q->where('purchase_return_id', $invoice->id);
                })
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo) // 🔥 IMPORTANT
                ->sum('qty');

            // ✅ Validation
            if (($returnedQty + $qty) > $purchaseQty) {
                throw new \Exception("Return exceeded for item {$itemId} (Batch: {$batchNo})");
            }

            // ✅ Calculate total
            $lineTotal = $qty * $price;
            $totalAmount += $lineTotal;

            // ✅ Save detail with batch
            CreditNoteDetail::create([
                'credit_note_id' => $creditNote->id,
                'item_id'        => $itemId,
                'qty'            => $qty,
                'price'          => $price,
                'total'          => $lineTotal,
                'batch_no'       => $batchNo, // ✅ FINAL
            ]);
        }

        // ✅ Update total
        $creditNote->update([
            'total_amount' => $totalAmount
        ]);

        // JournalEntryHelper::createCreditNoteJournal($creditNote);

        return $creditNote->load([
            'details',
            'supplier',
            'purchaseReturn'
        ]);
    });
}

    public function list($request)
    {
        $data = CreditNoteHeader::with([
            'customer:id,business_name,osa_code',
            'salesman:id,name',
            'distributor:id,warehouse_name,warehouse_code',
            'purchaseInvoice:id,invoice_code',
            'creditNoteDetails:id,credit_note_id,item_id,qty,price,total'
        ])
        ->orderBy('id', 'asc')
        ->paginate(50);
        return [
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
                        'code' => $item->distributor->warehouse_code,
                        'name' => $item->distributor->warehouse_name
                    ] : null,
                    // ✅ Details
                    'details' => $item->creditNoteDetails->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'item_id' => $detail->item_id,
                            'qty' => $detail->qty,
                            'price' => $detail->price,
                            'total' => $detail->total,
                        ];
                    }),
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
        ];
    }

    public function delete($creditNote)
    {
        $creditNote->delete();
        return true;
    }

 public function globalFilter($request)
{
    $query = CreditNoteHeader::query()
        ->with([
            'purchaseInvoice:id,invoice_code',
            'customer:id,business_name,osa_code',
            'distributor:id,warehouse_name,warehouse_code',
            'details:id,credit_note_id,item_id,qty,price,total'
        ]);
        
    // if ($request->from_date && $request->to_date) {
    //     $query->whereDate('created_at', '>=', $request->from_date)
    //           ->whereDate('created_at', '<=', $request->to_date);
    // }
    // if ($request->distributor_id) {
    //     $query->where('distributor_id', $request->distributor_id);
    // }

    $filter = $request->input('filter', []);

    $fromDate = $filter['from_date'] ?? null;
    $toDate   = $filter['to_date'] ?? null;
    $distributorId = $filter['distributor_id'] ?? $request->distributor_id ?? null;

    // ✅ DATE FILTER
    if ($fromDate && $toDate) {
        $query->whereDate('created_at', '>=', $fromDate)
              ->whereDate('created_at', '<=', $toDate);
    }

    // ✅ DISTRIBUTOR FILTER
    if ($distributorId) {
        $query->where('distributor_id', $distributorId);
    }

    $limit = $request->limit ?? 50;
    $data = $query->orderBy('id', 'asc')->paginate($limit);

    $formattedData = collect($data->items())->map(function ($item) {
        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'credit_note_no' => $item->credit_note_no,

            'purchase_invoice' => [
                'id' => $item->purchaseInvoice->id ?? null,
                'invoice_code' => $item->purchaseInvoice->invoice_code ?? null,
            ],

            'supplier_id' => $item->supplier_id,
            'total_amount' => $item->total_amount,
            'reason' => $item->reason,
            'status' => $item->status,

            'customer' => [
                'id' => $item->customer->id ?? null,
                'code' => $item->customer->osa_code ?? null,
                'name' => $item->customer->business_name ?? null,
            ],
            // 'salesman' => null,
            'salesman' => $item->salesman ? [
                'id' => $item->salesman->id,
                'name' => $item->salesman->name
            ] : null,
            'distributor' => [
                'id' => $item->distributor->id ?? null,
                'code' => $item->distributor->warehouse_code ?? null,
                'name' => $item->distributor->warehouse_name ?? null,
            ],

            'details' => collect($item->details)->map(function ($d) {
                return [
                    'id' => $d->id,
                    'item_id' => $d->item_id,
                    'qty' => $d->qty,
                    'price' => $d->price,
                    'total' => (string)$d->total,
                ];
            }),
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    });
    return [
        'status' => 'success',
        'code' => 200,
        'data' => $formattedData,
        'pagination' => [
            'page' => $data->currentPage(),
            'limit' => (int)$limit,
            'totalPages' => $data->lastPage(),
            'totalRecords' => $data->total(),
        ]
    ];
}
}