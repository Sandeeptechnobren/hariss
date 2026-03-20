<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'header_id'        => $this->header_id,
            'from_qty'  => (int) $this->from_qty,
            'to_qty'    => (int) $this->to_qty,
            'free_qty'  => (int) $this->free_qty,

            // OFFER
            // 'offer_item_id' => $this->offer_item_id,
            // 'offer_uom'     => $this->offer_uom,

            // // PERCENTAGE
            // 'percentage_item_id'       => $this->percentage_item_id,
            // 'percentage_item_category' => $this->percentage_item_category,
            // 'percentage'               => $this->percentage !== null
            //     ? (float) $this->percentage
            //     : null,
        ];
    }
}
