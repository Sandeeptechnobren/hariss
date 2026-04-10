<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use App\Models\Hariss_Transaction\Web\CreditNoteDetail;
use Illuminate\Support\Facades\DB;
use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
use App\Models\WarehouseStock;

class CreditNoteService
{
    // public function create($data)
    // {
    //     return DB::transaction(function () use ($data) {

    //         $totalAmount = 0;

    //         $creditNote = CreditNoteHeader::create([
    //             'credit_note_no' => $data['credit_note_no'],
    //             'purchase_invoice_id' => $data['purchase_invoice_id'],
    //             'supplier_id' => $data['supplier_id'],
    //             'customer_id' => $data['customer_id'],
    //             'salesman_id' => $data['salesman_id'] ?? null,
    //             'distributor_id' => $data['distributor_id'],
    //             'reason' => $data['reason'] ?? null,
    //             'status' => $data['status'] ?? 1,
    //             'total_amount' => 0
    //         ]);

    //         foreach ($data['details'] as $item) {

    //             $lineTotal = $item['qty'] * $item['price'];
    //             $totalAmount += $lineTotal;

    //             CreditNoteDetail::create([
    //                 'credit_note_id' => $creditNote->id,
    //                 'purchase_invoice_id' => $data['purchase_invoice_id'],
    //                 'item_id' => $item['item_id'],
    //                 'qty' => $item['qty'],
    //                 'price' => $item['price'],
    //                 'total' => $lineTotal,
    //             ]);
    //         }

    //         $creditNote->update([
    //             'total_amount' => $totalAmount
    //         ]);

    //         return $creditNote->load(['details', 'supplier', 'purchaseInvoice']);
    //     });
    // }

    public function create($data)
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = 0;
            $invoice = HTInvoiceHeader::findOrFail($data['purchase_invoice_id']);
             //dd($invoice);
            $creditNote = CreditNoteHeader::create([
                'credit_note_no' => $data['credit_note_no'],
                'purchase_invoice_id' => $invoice->id,
                'supplier_id' => $invoice->sap_id,  
                'customer_id' => $data['customer_id'] ?? null,
                'salesman_id' => $data['salesman_id'] ?? null,
                'distributor_id' => $data['distributor_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => 1,  
                'total_amount' => 0
            ]);
            foreach ($data['details'] as $item) {
                $itemId = $item['item_id'];
                $qty    = $item['qty'];
                $price  = $item['price'];
                // $purchaseQty = HTInvoiceDetail::where('header_id', $invoice->id)
                //     ->where('item_id', $itemId)
                //      ->value('quantity');
                // if (!$purchaseQty) {
                //     throw new \Exception("Item not found in invoice: {$itemId}");
                // }
                // $returnedQty = CreditNoteDetail::where('purchase_invoice_id', $invoice->id)
                //     ->where('item_id', $itemId)
                //     ->sum('qty');
                // if (($returnedQty + $qty) > $purchaseQty) {
                //     throw new \Exception("Return exceeded for item {$itemId}");
                // }
                $purchaseQty = HTInvoiceDetail::where('header_id', $invoice->id)
                    ->where('item_id', $itemId)
                    ->sum('quantity');
                if ($purchaseQty === null) {
                    throw new \Exception("Item not found in invoice: {$itemId}");
                }
                $returnedQty = CreditNoteDetail::where('purchase_invoice_id', $invoice->id)
                    ->where('item_id', $itemId)
                    ->sum('qty');

                if (($returnedQty + $qty) > $purchaseQty) {
                    throw new \Exception("Return exceeded for item {$itemId}");
                }
                $lineTotal = $qty * $price;
                $totalAmount += $lineTotal;
                CreditNoteDetail::create([
                    'credit_note_id' => $creditNote->id,
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                ]);
                // $stock = WarehouseStock::where('item_id', $itemId)
                //     ->where('warehouse_id', $invoice->warehouse_id)
                //     ->lockForUpdate()
                //     ->first();
                // if (!$stock || $stock->qty < $qty) {
                //     throw new \Exception("Insufficient stock for item {$itemId}");
                // }
                // $stock->decrement('qty', $qty);
                $stock = WarehouseStock::where('item_id', $itemId)
                       ->where('warehouse_id', $invoice->warehouse_id)
                       ->lockForUpdate()
                       ->first();
                if (!$stock) {
                        WarehouseStock::create([
                        'item_id' => $itemId,
                        'warehouse_id' => $invoice->warehouse_id,
                        'qty' => $qty
                    ]);
                } else {
                    // return hai → stock badhega
                    $stock->increment('qty', $qty);
                }
            }
            $creditNote->update([
                'total_amount' => $totalAmount
            ]);
            $invoice->decrement('total', $totalAmount);
            return $creditNote->load(['details', 'supplier', 'purchaseInvoice']);
        });
    }

    // public function list($request)
    // {    
    //     $perPage = min($request->get('per_page', 50), 100);

    //     return CreditNoteHeader::with(['details', 'supplier', 'purchaseInvoice'])
    //         ->when($request->supplier_id, function ($q) use ($request) {
    //             $q->where('supplier_id', $request->supplier_id);
    //         })
    //         ->latest()
    //         ->paginate($perPage);
    // }

    // public function list($request)
    // {        
    //     $perPage = min($request->get('per_page', 50), 100);
    //     return CreditNoteHeader::with(['details', 'supplier', 'purchaseInvoice'])
    //        ->oldest()
    //        ->paginate($perPage);
    // }

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
}