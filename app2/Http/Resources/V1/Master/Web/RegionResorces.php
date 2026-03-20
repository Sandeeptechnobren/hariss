<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResorces extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'region_code'  => $this->region_code,
            'region_name'  => $this->region_name,
            'status'      => $this->status,
            'country_id'   => $this->country?->only(['id', 'country_name']),
        ];
    }
}
