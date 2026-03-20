<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionDetailResource extends JsonResource
{
     public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'header_id' => $this->header_id,
            'lower_qty' => $this->lower_qty,
            'upper_qty' => $this->upper_qty,
            'free_qty' => $this->free_qty,
            'uuid' => $this->uuid,
        ];
    }
}
