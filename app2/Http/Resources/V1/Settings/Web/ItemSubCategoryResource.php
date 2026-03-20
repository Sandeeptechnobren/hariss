<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemSubCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'sub_category_name' => $this->sub_category_name,
            'sub_category_code' =>$this->sub_category_code,
            'status'        => $this->status,
            'category'  =>$this->category,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
