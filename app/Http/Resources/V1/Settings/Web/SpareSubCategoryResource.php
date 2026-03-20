<?php

namespace App\Http\Resources\V1\Settings\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpareSubCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'spare_subcategory_name' => $this->spare_subcategory_name,
            'spare_category_id' => $this->spare_category_id,
            'spare_category_name' => $this->category->spare_category_name ?? null,
            'status' => $this->status,
        ];
    }

}