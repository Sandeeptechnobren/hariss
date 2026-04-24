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

    public function create($data)
{ 
    return DB::transaction(function () use ($data) {

        $totalAmount = 0;

        $invoice = HtReturnHeader::findOrFail($data['purchase_return_id']);

        $creditNote = CreditNoteHeader::create([
            'credit_note_no'     => $data['credit_note_no'],
            'purchase_return_id' => $invoice->id,
            'supplier_id'        => $invoice->sap_id,
            'customer_id'        => $data['customer_id'] ?? null,
            'salesman_id'        => $data['salesman_id'] ?? null,
            'distributor_id'     => $data['distributor_id'] ?? null,
            'reason'             => $data['reason'] ?? null,
            'total_net'          => $data['total_net'] ?? null,
            'total_vat'          => $data['total_vat'] ?? null,
            'status'             => 1,
            'total_amount'       => 0
        ]);

        foreach ($data['details'] as $item) {
      
            $itemId  = $item['item_id'];
            $qty     = $item['qty'];
            $price   = $item['price'];
            $batchNo = $item['batch_no'];
            $net     = $item['net'];
            $vat     = $item['vat'];

            $purchaseQty = HtReturnDetail::where('header_id', $invoice->id)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo) 
                ->sum('qty');

            if ($purchaseQty <= 0) {
                throw new \Exception("Item or batch not found: {$itemId} / {$batchNo}");
            }

            $returnedQty = CreditNoteDetail::whereHas('header', function ($q) use ($invoice) {
                    $q->where('purchase_return_id', $invoice->id);
                })
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo)
                ->sum('qty');

            if (($returnedQty + $qty) > $purchaseQty) {
                throw new \Exception("Return exceeded for item {$itemId} (Batch: {$batchNo})");
            }

            $lineTotal = $qty * $price;
            $totalAmount += $lineTotal;

            CreditNoteDetail::create([
                'credit_note_id' => $creditNote->id,
                'item_id'        => $itemId,
                'qty'            => $qty,
                'price'          => $price,
                'total'          => $lineTotal,
                'batch_no'       => $batchNo,
                'net'            => $net,
                'vat'            => $vat,

            ]);
        }
        $creditNote->update([
            'total_amount' => $totalAmount
        ]);

        return $creditNote->load([
            'details',
            'supplier',
            'purchaseReturn'
        ]);
    });
}

    public function list($request)
{
    $query = CreditNoteHeader::with([
        'customer:id,business_name,osa_code',
        'salesman:id,name',
        'distributor:id,warehouse_name,warehouse_code',
        'purchasereturn:id,return_code',
        'creditNoteDetails:id,credit_note_id,item_id,qty,price,total,net,vat'
    ]);

    $limit = $request->limit ?? 50;

    if ($request->warehouse_id) {
        $warehouseIds = explode(',', $request->warehouse_id);
        $query->whereIn('distributor_id', $warehouseIds);
    }

    $data = $query->orderBy('id', 'desc')->paginate($limit);
    return [
        'data' => collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'credit_note_no' => $item->credit_note_no,

                'purchase_return' => $item->purchasereturn ? [
                    'id' => $item->purchasereturn->id,
                    'return_code' => $item->purchasereturn->return_code
                ] : null,
                'supplier_id' => $item->supplier_id,
                'total_amount' => $item->total_amount,
                'reason' => $item->reason,
                'total_net' => $item->total_net,
                'total_vat' => $item->total_vat,
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
                'details' => $item->creditNoteDetails->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'item_id' => $detail->item_id,
                        'qty' => $detail->qty,
                        'price' => $detail->price,
                        'total' => $detail->total,
                        'net' => $detail->net,
                        'vat' => $detail->vat,
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
    $parseIds = function ($value) {
        if (empty($value)) return [];
        if (is_array($value)) return array_filter($value);
        return array_filter(array_map('trim', explode(',', $value)));
    };

    $query = CreditNoteHeader::query()
        ->with([
            'purchasereturn:id,return_code',
            'customer:id,business_name,osa_code',
            'distributor:id,warehouse_name,warehouse_code,company,region_id,area_id',
            'details:id,credit_note_id,item_id,qty,price,batch_no,net,vat,total',
            'salesman:id,name'
        ]);

    $filter = $request->input('filter', []);
    $fromDate = $filter['from_date'] ?? null;
    $toDate   = $filter['to_date'] ?? null;

    if ($fromDate && $toDate) {
        $query->whereDate('created_at', '>=', $fromDate)
              ->whereDate('created_at', '<=', $toDate);
    }

    $companyIds = $parseIds($filter['company_id'] ?? null);
    $regionIds  = $parseIds($filter['region_id'] ?? null);
    $areaIds    = $parseIds($filter['area_id'] ?? null);

    if (!empty($companyIds) || !empty($regionIds) || !empty($areaIds)) {
        $query->whereHas('distributor', function ($q) use ($companyIds, $regionIds, $areaIds) {

            if (!empty($companyIds)) {
                $q->whereIn('company', $companyIds);
            }
            if (!empty($regionIds)) {
                $q->whereIn('region_id', $regionIds);
            }
            if (!empty($areaIds)) {
                $q->whereIn('area_id', $areaIds);
            }
        });
    }

    $warehouseIds = $parseIds($filter['warehouse_id'] ?? null);
    if (!empty($warehouseIds)) {
        $query->whereIn('distributor_id', $warehouseIds);
    }

    $salesmanIds = $parseIds($filter['salesman_id'] ?? null);
    if (!empty($salesmanIds)) {
        $query->whereIn('salesman_id', $salesmanIds);
    }

    if (!empty($filter['status'])) {
        $query->where('status', $filter['status']);
    }
    $limit = $request->per_page ?? $request->limit ?? 50;
    $data = $query->orderBy('id', 'asc')->paginate($limit);
    $formattedData = collect($data->items())->map(function ($item) {
        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'credit_note_no' => $item->credit_note_no,
            'purchase_return' => $item->purchasereturn ? [
                'id' => $item->purchasereturn->id,
                'return_code' => $item->purchasereturn->return_code,
            ] : null,
            'supplier_id' => $item->supplier_id,
            'total_amount' => $item->total_amount,
            'reason' => $item->reason,
            'total_net' => $item->total_net,
            'total_vat' => $item->total_vat,
            'status' => $item->status,
            'customer' => $item->customer ? [
                'id' => $item->customer->id,
                'code' => $item->customer->osa_code,
                'name' => $item->customer->business_name,
            ] : null,
            'salesman' => $item->salesman ? [
                'id' => $item->salesman->id,
                'name' => $item->salesman->name
            ] : null,
            'distributor' => $item->distributor ? [
                'id' => $item->distributor->id,
                'code' => $item->distributor->warehouse_code,
                'name' => $item->distributor->warehouse_name,
            ] : null,
            'details' => collect($item->details)->map(function ($d) {
                return [
                    'id' => $d->id,
                    'item_id' => $d->item_id,
                    'qty' => $d->qty,
                    'batch_no' => $d->batch_no,
                    'price' => $d->price,
                    'total' => (string) $d->total,
                    'net' => $d->net,
                    'vat' => $d->vat,
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
            'limit' => (int) $limit,
            'totalPages' => $data->lastPage(),
            'totalRecords' => $data->total(),
        ]
    ];
}
}