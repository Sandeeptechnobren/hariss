<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailBasedReturn extends JsonResource
{
    public function toArray(Request $request): ?array
    {
        $returnedQty = $this->getAttribute('returned_qty') ?? 0;

        $remainingQty = $this->quantity - $returnedQty;

        if ($remainingQty <= 0) {
            return null;
        }

        return [
            'item_id'      => $this->item_id,
            'item_code'    => $this->item->code ?? null,
            'erp_code'    => $this->item->erp_code ?? null,
            'item_name'    => $this->item->name ?? null,

            'uom_id'       => $this->uom,
            'uom_name'     => $this->uoms->name ?? null,

            // ✅ NOW ALWAYS CORRECT
            'invoice_qty'  => $this->quantity,
            'returned_qty' => $returnedQty,
            'quantity'     => $remainingQty,

            'itemvalue'    => $this->itemvalue,
            'vat'          => $this->vat,
            'pre_vat'      => $this->pre_vat,
            'net_total'    => $this->net_total,
            'item_total'   => $this->item_total,

            'item_uoms' => $this->itemUoms ? [
                'name'     => $this->itemUoms->name,
                'uom_id'   => $this->itemUoms->uom_id,
                'uom_type' => $this->itemUoms->uom_type,
                'upc'      => $this->itemUoms->upc,
                'price'    => $this->itemUoms->price,
            ] : null,
        ];
    }
}
