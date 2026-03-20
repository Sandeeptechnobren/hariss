<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerSubCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer_category_id' => $this->customer_category_id,
            'customer_sub_category_code' => $this->customer_sub_category_code,
            'customer_sub_category_name' => $this->customer_sub_category_name,
            'status' => $this->status,
            'created_user' => $this->created_user,
            'updated_user' => $this->updated_user,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
        ];
    }
}
