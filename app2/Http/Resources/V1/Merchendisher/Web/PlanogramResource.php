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
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'code'           => $this->code,
            'name'           => $this->name,
            'valid_from'     => $this->valid_from,
            'valid_to'       => $this->valid_to,
            'merchendishers' => $this->getMerchandishers(),
            'customers'      => $this->getCustomers(),
            'shelves'        => $this->getShelves(),
            'images'         => $this->formatImages(),
            'created_at'     => optional($this->created_at)->toDateTimeString(),
            'updated_at'     => optional($this->updated_at)->toDateTimeString(),
        ];
    }

/**
 * Format images in the structure:
 * {
 *   merchendisher_id => {
 *      customer_id => [
 *          { shelf_id, image },
 *          ...
 *      ],
 *      ...
 *   },
 *   ...
 * }
 */
protected function formatImages()
{
    $images = [];
    
    foreach ($this->planogramImages as $image) {
        $merchId = (string) $image->merchandiser_id;
        $custId = (string) $image->customer_id;

        if (!isset($images[$merchId])) {
            $images[$merchId] = [];
        }
        if (!isset($images[$merchId][$custId])) {
            $images[$merchId][$custId] = [];
        }

        $images[$merchId][$custId][] = [
            'shelf_id' => $image->shelf_id,
            'image'    => $image->image,
        ];
    }

    return $images;
}
}
