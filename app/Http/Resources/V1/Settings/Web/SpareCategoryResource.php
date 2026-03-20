<?php

namespace App\Http\Resources\V1\Settings\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpareCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'spare_category_name' => $this->spare_category_name,
            'status' => $this->status,
        ];
    }

}