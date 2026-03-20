<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DamageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->id,
            'date'                 =>$this->date,
            'shelf_id'             => $this->shelf_id,
            'shelf_name'          => $this->shelf?->shelf_name,
            'shelf_code'          => $this->shelf?->code,            
            'item_id'              => $this->item_id,
            'item_name'            => $this->item?->name,
            'item_code'            => $this->item?->code,
            'item_uom'             => $this->item?->itemUoms?->first()?->uom?->name,
            'damage_qty'           => $this->damage_qty,
            'expiry_qty'           => $this->expiry_qty,
            'salable_qty'          => $this->salable_qty,
            'merchandisher_id'     => $this->merchandisher_id,
            'merchandisher_name'   => $this->merchandisher?->name,
            'merchandisher_code'   => $this->merchandisher?->osa_code,
            'customer_id'          => $this->customer_id,
            'customer_name'        => $this->customer?->business_name,
            'customer_code'        => $this->customer?->osa_code,
        ];
    }
}
