<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class RewardCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'osa_code'     => $this->osa_code,
            'name'         => $this->name,
            'image'        => $this->image,
            'points_required'  => $this->points_required,
            'stock_qty'  => $this->stock_qty,
            'type'       => $this->type,
        ]; 
    }
}