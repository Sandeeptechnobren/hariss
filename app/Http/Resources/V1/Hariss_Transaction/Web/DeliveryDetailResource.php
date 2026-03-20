<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid'       => $this->uuid,
            'item_id'    => $this->item_id,
            'item_code'  => $this->item->code ?? null,
            'erp_code'  => $this->item->erp_code ?? null,
            'item_name'  => $this->item->name ?? null,
            'uom_id'     => $this->uom_id,
            'uom_name'   => $this->uoms->name ?? null,
            'discount_id' => $this->discount_id,
            'promotion_id' => $this->promotion_id,
            'parent_id'  => $this->parent_id,
            'item_price' => $this->item_price,
            'quantity'   => $this->quantity,
            'discount'   => $this->discount,
            'gross_total' => $this->gross_total,
            'promotion'  => $this->promotion,
            'net'        => $this->net,
            'excise'     => $this->excise,
            'pre_vat'    => $this->pre_vat,
            'vat'        => $this->vat,
            'total'      => $this->total
        ];
    }
}
