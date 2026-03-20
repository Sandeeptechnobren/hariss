<?php

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CapsDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,

            'item_id'         => $this->item_id,
            'item_code'       => $this->item->code ?? null,
            'item_name'       => $this->item->name ?? null,

            'uom_id'          => $this->uom_id,
            'uom_name'        => $this->uoms->name ?? null,
            'uom_type'        => $this->uoms->uom_type ?? null,
            
            'quantity'        => $this->quantity,
            'receive_qty'     => $this->receive_qty,
            'receive_amount'  => $this->receive_amount,
            'receive_date'    => $this->receive_date,
            'remarks'         => $this->remarks,
            'remarks2'        => $this->remarks2,
            'status'          => $this->status,

        ];
    }
}
