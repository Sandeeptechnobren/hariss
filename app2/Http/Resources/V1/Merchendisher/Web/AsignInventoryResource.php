<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsignInventoryResource extends JsonResource
{
     public function toArray($request)
    {
        return [
            'uuid'        => $this->uuid,
            'item' => [
                'id'   => $this->item_id,
                'name' => $this->item->name ?? null,
            ],
            'item_uom'    => $this->item_uom,
            'StockInStore'=>[
                 'id' => $this->header_id,
                 'name'=> $this->StockInStore->activity_name ?? null,
            ],
            'capacity'    => $this->capacity,
        ];
    }
}
