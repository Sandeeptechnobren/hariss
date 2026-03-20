<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShelveResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'shelf_name' => $this->shelf_name,
            'height' => $this->height,
            'width' => $this->width,
            'depth' => $this->depth,
            'customer_ids' => $this->customer_ids,
            'merchendiser_ids' => $this->merchendiser_ids,
            'valid_from' => $this->valid_from,
            'valid_to' => $this->valid_to,

            'items' => ShelveItemResource::collection($this->shelfitem ?? [])
        ];
    }
}   