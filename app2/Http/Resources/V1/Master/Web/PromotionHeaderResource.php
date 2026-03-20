<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'key_combination' => $this->key_combination,
            'promotion_name' => $this->promotion_name,
            'description' => $this->description,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'warehouse_ids' => $this->warehouse_ids,
            'manager_ids' => $this->manager_ids,
            'projects_id' => $this->projects_id,
            'included_customer_id' => $this->included_customer_id,
            'excluded_customer_ids' => $this->excluded_customer_ids,
            'assignment_uom' => $this->assignment_uom,
            'qualification_uom' => $this->qualification_uom,
            'outlet_channel_id' => $this->outlet_channel_id,
            'customer_category' => $this->customerCategory ? $this->customerCategory->customer_category_name : null,
            'bought_item_ids' => $this->bought_item_ids,
            'bonus_item_ids' => $this->bonus_item_ids,
            'status' => $this->status,

            'promotion_details' => PromotionDetailResource::collection($this->whenLoaded('promotionDetails')),
        ];
    }
}
