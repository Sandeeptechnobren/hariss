<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
            return [
            'item_id'           => $this->item_id,
            'item_code'         => $this->item->code ?? null,
            'item_name'         => $this->item->name ?? null,
            'erp_code'          => $this->item->erp_code ?? null,
            'uom_id'            => $this->uom,
            'uom_name'          => $this->uoms->name ?? null,
            'quantity'          => $this->quantity,
            'itemvalue'         => $this->itemvalue,
            'vat'               => $this->vat,
            'pre_vat'           => $this->pre_vat,
            'net_total'         => $this->net_total,
            'item_total'        => $this->item_total,
            // 'itemprice'         => $this->itemprice->buom_ctn_price ?? null,
            // 'item_price'        => $this->itemprice->auom_pc_price ?? null,
            'item_uoms' => $this->itemUoms ? [
            'name'     => $this->itemUoms->name,
            'uom_id'   => $this->itemUoms->uom_id,
            'uom_type' => $this->itemUoms->uom_type,
            'upc'      => $this->itemUoms->upc,
            'price'   => $this->itemUoms->price,
            ] : null,

        ];
    }
}