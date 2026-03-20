<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentDeliveryDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'header_id' => $this->header_id,
            // 'item_id' => $this->item_id,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name,
                'erp_code'=> $this->item->erp_code,
            ] : null,
            'item_uoms' => $this->item->itemUoms->map(function ($uom) {
                return [
                    'id' => $uom->id,
                    'name' => $uom->name,
                    'price' => (float) $uom->price,
                    'upc' => $uom->upc,
                    'uom_type' => $uom->uom_type,
                ];
            }) ?? [],
            'uom_id' => $this->uom_id,
            'uom_name' => $this->itemUom->name ?? null,
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
