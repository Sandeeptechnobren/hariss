<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

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

            // If these methods already exist, keep them
            'merchendishers'   => $this->getMerchandishers(),
            'customers'        => $this->getCustomers(),

            // ✅ Images from comma-separated string
            'images'           => $this->imagesToArray(),

            'created_at'       => optional($this->created_at)->toDateTimeString(),
            'updated_at'       => optional($this->updated_at)->toDateTimeString(),
        ];
    }

    /**
     * Convert comma-separated images string to array
     */
protected function imagesToArray(): array
{
    if (empty($this->images)) {
        return [];
    }

    return array_values(array_map(function ($image) {
        return rtrim(config('app.url'), '/') . '/public' . trim($image);
    }, array_filter(explode(',', $this->images))));
}
}
