<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'osa_code'        => $this->osa_code,
            'uuid'             => $this->uuid,
            'item'          => $this->item,
            'item_category'      => $this->itemCategory,
            'customer_id'      => $this->customer_id,
            'customer_channel_id' => $this->customer_channel_id,

            'discount_type'    => $this->discountType,
            'discount_value'   => $this->discount_value,
            'min_quantity'     => $this->min_quantity,
            'min_order_value'  => $this->min_order_value,

            'start_date'       => $this->start_date,
            'end_date'         => $this->end_date,
            'status'           => (int) $this->status,
        ];
    }
}
