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
        'merchendiser_name' => optional($this->merchendiser)->name,
        'merchendiser_code' => optional($this->merchendiser)->osa_code,
        'customer_id' => $this->customer_id,
        'customer_name' => optional($this->customer)->business_name,
        'customer_code' => optional($this->customer)->osa_code,
        'item_id' => $this->item_id,
        'item_name' => optional($this->item)->name,
        'item_code' => optional($this->item)->code,
        'type' => $this->type,
        'complaint' => $this->complaint,
        'image' => $this->image,
        'created_at' => $this->created_at,
      ];
    }
}