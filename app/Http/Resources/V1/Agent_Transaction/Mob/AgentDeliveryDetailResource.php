<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentDeliveryDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'header_id' => $this->header_id,
            'item_id' => $this->item_id,
            'uom_id' => $this->uom_id,
            'discount_id' => $this->discount_id,
            'promotion_id' => $this->promotion_id,
            'parent_id' => $this->parent_id,
            'item_price' => $this->item_price,
            'quantity' => $this->quantity,
            'vat' => $this->vat,
            'discount' => $this->discount,
            'gross_total' => $this->gross_total,
            'net_total' => $this->net_total,
            'total' => $this->total,
            'is_promotional' => (bool) $this->is_promotional
        ];
    }
}
