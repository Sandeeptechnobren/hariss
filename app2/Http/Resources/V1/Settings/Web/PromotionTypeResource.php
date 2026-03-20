<?php

namespace App\Http\Resources\V1\Settings\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'created_user' => $this->createdBy,
            'updated_user' => $this->updatedBy,
            'created_date' => $this->created_date,
            'updated_date' => $this->updated_date,
        ];
    }
}
