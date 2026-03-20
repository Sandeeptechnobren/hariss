<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class ShelveItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                   => $this->id,
            'uuid'              => $this->uuid,
            'shelf_id'            => $this->shelf_id,
            'item_id'           => $this->product_id,
            'capacity'            => $this->capacity,
            'total_no_of_fatching'=> $this->total_no_of_fatching,
        ];
    }
}
