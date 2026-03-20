<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanogramPostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'planogram_id'    => $this->planogram_id,
            'merchendisher_id'=> $this->merchendisher_id,
            'date'            => $this->date,
            'customer_id'     => $this->customer_id,
            'shelf_id'        => $this->shelf_id,
            'before_image'    => $this->before_image,
            'after_image'     => $this->after_image,
        ];
    }
}
