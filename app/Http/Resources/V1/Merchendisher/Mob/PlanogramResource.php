<?php

namespace App\Http\Resources\V1\Merchendisher\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanogramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'code'             => $this->code,
            'name'             => $this->name,
            'valid_from'       => $this->valid_from,
            'valid_to'         => $this->valid_to,
            'merchendishers' => $this->merchendisher_id
                ? array_map('intval', explode(',', $this->merchendisher_id))
                : [],
            'customers' => $this->customer_id
                ? array_map('intval', explode(',', $this->customer_id))
                : [],
            'images'           => $this->imagesToArray(),
        ];
    }
protected function imagesToArray(): array
{
    if (empty($this->images)) {
        return [];
    }

    return array_values(array_map(function ($image) {
        return trim($image);
    }, array_filter(explode(',', $this->images))));
}
}
