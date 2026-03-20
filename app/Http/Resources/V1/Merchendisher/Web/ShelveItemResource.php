<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class ShelveItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->id,
            'uuid'              => $this->uuid,
            'shelf_id'            => $this->shelf_id,
            'shelf' => [
                'id'        => $this->shelf?->id,
                'name'      => $this->shelf?->shelf_name,
                'code'      => $this->shelf?->code,
            ],
            'item_id'           => $this->product_id,
            'item' => [
                'id'        => $this->item?->id,
                'name'      => $this->item?->name,
                'code'      => $this->item?->code,
                'uom'       => $this->item?->itemUoms?->first()?->uom?->name,
            ],
            'capacity'            => $this->capacity,
            'total_no_of_fatching'=> $this->total_no_of_fatching,
        ];
    }
}
