<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'osa_code'            => $this->osa_code,

            'discount_name'       => $this->discount_name,
            'discount_apply_on'   => $this->discount_apply_on,
            'discount_type'       => $this->discount_type,
            'bundle_combination'  => $this->bundle_combination,

            'status'              => (string) $this->status,
            'from_date'           => $this->from_date,
            'to_date'             => $this->to_date,

            'sales_team_type' => $this->sales_team_type
                ? explode(',', $this->sales_team_type)
                : [],

            'project_list' => $this->project_list
                ? explode(',', $this->project_list)
                : [],

            'items' => $this->items
                ? explode(',', $this->items)
                : [],

            'item_category' => $this->item_category
                ? explode(',', $this->item_category)
                : [],

            // 'uom' => (string) $this->uom,

            'location' => $this->location
                ? explode(',', $this->location)
                : [],

            'customer' => $this->customer
                ? explode(',', $this->customer)
                : [],

            'header' => [
                'headerMinAmount' => $this->order_amount,
                'headerRate'      => $this->discount_amount_percentage,
            ],

            'discount_details' => DiscountDetailResource::collection(
                $this->whenLoaded('details')
            ),

            'key' => [
                'Location' => $this->key_location
                    ? explode(',', $this->key_location)
                    : [],

                'Customer' => $this->key_customer
                    ? explode(',', $this->key_customer)
                    : [],

                'Item' => $this->key_item
                    ? explode(',', $this->key_item)
                    : [],
            ],

        ];
    }
}
