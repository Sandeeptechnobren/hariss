<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanogramImageResource extends JsonResource
{
     public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
             'customer' => [
            'id'   => $this->customer_id,
            'name' => $this->customer->business_name ?? null,
            ],
            'merchandiser' => [
                'id'   => $this->merchandiser_id,
                'name' => $this->merchandiser->name ?? null,
            ],
            'shelf' => [
                'id'   => $this->shelf_id,
                'name' => $this->shelf->shelf_name ?? null,
            ],
            'image_url'       => url($this->image), 
        ];
    }
}
