<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid'       => $this->uuid,
            'item_id'    => $this->item_id,
            'item_code'  => $this->item->code ?? null,
            'item_name'  => $this->item->name ?? null,
            'uom_id'     => $this->uom_id,
            'uom_name'   => $this->uoms->name ?? null,
            'uom_id'     => $this->uom_id,
            'quantity'   => $this->quantity,
            'item_price' => $this->item_price,
            'discount'   => $this->discount,
            'gross_total' => $this->gross_total,
            'net'        => $this->net,
            'vat'        => $this->vat,
            'total'      => $this->total,
            'batch_number' => $this->batch_number
        ];
    }
}
