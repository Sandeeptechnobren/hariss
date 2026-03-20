<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'discount_code'         => $this->discount_code,
            'discount_name'         => $this->discount_name,
            'discount_status'       => $this->discount_status,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
            'updated_user' => $this->updated_user,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
        ];
    }
}
