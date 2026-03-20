<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'header_id'      => $this->header_id,
            'header_code'    => $this->header_code,
            'item_id'        => $this->item_id,
            'item_code'      => $this->item->code ?? null,
            'item_name'      => $this->item->name ?? null,

            'uom_id'         => $this->uom_id,
            'uom_name'       => $this->uom->name ?? null,

            'returntype_id'         => $this->return_type,
            'returntype_name'       => $this->returntype->return_type ?? null,
            
            'return_reason_id'       => $this->return_reason,
            'return_reason'       => $this->returnreason->reson ?? null,

            'discount_id'    => $this->discount_id,
            'discount_code'  => $this->discount->osa_code ?? null,

            'promotion_id'   => $this->promotion_id,
            'promotion_name' => $this->promotion->promotion_name ?? null,

            'parent_id'      => $this->parent_id,

            'item_price'     => $this->item_price,
            'item_quantity'  => $this->item_quantity,
            'vat'            => $this->vat,
            'discount'       => $this->discount,
            'gross_total'    => $this->gross_total,
            'net_total'      => $this->net_total,
            'total'          => $this->total,

            'is_promotional' => (bool) $this->is_promotional,
            'status'         => $this->status,
        ];
    }
}
