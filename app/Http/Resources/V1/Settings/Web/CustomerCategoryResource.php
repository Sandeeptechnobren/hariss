<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'outlet_channel_id' => $this->outlet_channel_id,
            'customer_category_code' => $this->customer_category_code,
            'customer_category_name' => $this->customer_category_name,
            'status' => $this->status
        ];
    }
}
