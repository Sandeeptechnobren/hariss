<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'category_code' => $this->category_code,
            'category_name' => $this->category_name,
            'status'        => $this->status,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy
        ];
    }
}
