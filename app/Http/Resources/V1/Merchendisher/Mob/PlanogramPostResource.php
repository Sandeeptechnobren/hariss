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
            'merchendisher_name' => optional($this->merchendisher)->name,
            'merchendisher_code' => optional($this->merchendisher)->osa_code,
            'date'            => $this->date,
            'customer_id'     => $this->customer_id,
            'customer_name'   =>  optional($this->customer)->business_name,
            'customer_code'   =>  optional($this->customer)->osa_code,
            'shelf_id'        => $this->shelf_id,
            'shelf_name'      =>  optional($this->shelf)->shelf_name,
            'shelf_code'      => optional($this->shelf)->code,
            'before_image'    => $this->before_image,
            'after_image'     => $this->after_image,
        ];
    }
}
