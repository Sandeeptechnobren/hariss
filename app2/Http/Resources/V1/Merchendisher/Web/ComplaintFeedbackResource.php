<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintFeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
 return [
        'id' => $this->id,
        'uuid' => $this->uuid,
        'complaint_title' => $this->complaint_title,
        'complaint_code' => $this->complaint_code,
       'merchendiser_id' => $this->merchendiser_id,
        'merchendiser' => ($this->relationLoaded('merchendiser') && $this->merchendiser)
            ? [
                'id' => $this->merchendiser->id,
                'name' => $this->merchendiser->name,
            ]
            : null,

        'item_id' => $this->item_id,
        'item' => ($this->relationLoaded('item') && $this->item)
            ? [
                'id' => $this->item->id,
                'item_code' => $this->item->code,
                'item_name' => $this->item->name,
            ]
            : null,

        'type' => $this->type,
        'complaint' => $this->complaint,
        'image' => $this->image,
        'created_at' => $this->created_at,
      ];
    }
}