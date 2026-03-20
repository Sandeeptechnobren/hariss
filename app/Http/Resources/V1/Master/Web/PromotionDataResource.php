<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionDataResource extends JsonResource
{
    public function toArray($request): array
    {
        $details = $this->relationLoaded('promotionDetails')
            ? $this->promotionDetails
            : collect();

        $offers = $this->relationLoaded('offerItems')
            ? $this->offerItems
            : collect();

        $slabs = $this->relationLoaded('promotionalSlabs')
            ? $this->promotionalSlabs
            : collect();

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'osa_code'         => $this->osa_code,

            'promotion_name'     => $this->promotion_name,
            'promotion_type'     => $this->promotion_type,
            'bundle_combination' => $this->bundle_combination,
            'status'             => (string) $this->status,

            'from_date' => $this->from_date,
            'to_date'   => $this->to_date,

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

            'uom' => (string) $this->uom,

            // 'location' => $this->location
            //     ? explode(',', $this->location)
            //     : [],

            // 'customer' => $this->customer
            //     ? explode(',', $this->customer)
            //     : [],

            'promotion_details' => PromotionDetailResource::collection($details),

            'percentage_discounts' => $slabs
                ->map(fn($s) => [
                    'percentage_item_id'       => $s->item_id,
                    'item_name' => $s->percentageItem?->name,
                    'erp_code'  => $s->percentageItem?->erp_code,
                    'percentage_item_category' => $s->category,
                    'percentage'               => (float) $s->percentage,
                ])
                ->values(),

            'offer_items' => $offers
                ->unique(fn($o) => $o->offer_item_id . '_' . $o->uom)
                ->map(fn($o) => [
                    'item_id' => $o->offer_item_id,
                    'item_name' => $o->offerItem?->name,
                    'erp_code'  => $o->offerItem?->erp_code,
                    'uom'     => $o->uom,
                ])
                ->values(),

            // 'key' => [
            //     'Location' => $this->key_location
            //         ? explode(',', $this->key_location)
            //         : [],

            //     'Customer' => $this->key_customer
            //         ? explode(',', $this->key_customer)
            //         : [],

            //     'Item' => $this->key_item
            //         ? explode(',', $this->key_item)
            //         : [],
            // ],
        ];
    }
}
