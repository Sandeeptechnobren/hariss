<?php

namespace App\Http\Resources\V1\Assets\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class SpareResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'         => $this->uuid,
            'osa_code'      => $this->osa_code,
            'spare_name'      => $this->spare_name,
            'spare_categoryid' => $this->spare_categoryid,
            'spare_category_name' => $this->category->spare_category_name ?? null,
            'spare_subcategoryid'    => $this->spare_subcategoryid,
            'spare_subcategory_name' => $this->subcategory->spare_subcategory_name ?? null,
            'plant' => $this->plant,
        ];
    }
}