<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'header_id' => $this->header_id,
            'order_code' => $this->header->order_code,
            'item_id' => $this->item_id,
            'item_code'      => $this->item->code ?? null,
            'item_name'      => $this->item->name ?? null,
            'erp_code'       => $this->item->erp_code ?? null,
            'uom_id'         => $this->uom_id,
            'uom_name'       => $this->uoms->name ?? null,
            'item_price' => (float) $this->item_price,
            'quantity' => (float) $this->quantity,
            'vat' => (float) $this->vat,
            'discount' => (float) $this->discount,
            'gross_total' => (float) $this->gross_total,
            'net_total' => (float) $this->net_total,
            'total' => (float) $this->total,
            'item_uoms' => $this->item->itemUoms->map(function ($uom) {
                return [
                    'id' => $uom->id,
                    'name' => $uom->name,
                    'price' => (float) $uom->price,
                    'upc' => $uom->upc,
                    'uom_type' => $uom->uom_type,
                    'uom_id' => $uom->uom_id,
                ];
                     }) ?? [],
            'children' => OrderDetailResource::collection($this->whenLoaded('children')),
            // 'is_promotional' => $this->is_promotional,
            // 'promotion' => $this->promotion,
            // 'discount_id'    => $this->discount_id,
            // 'discount_code'  => $this->discount->osa_code ?? null,
            // 'promotion_id'   => $this->promotion_id,
            // 'promotion_name' => $this->promotion->promotion_name ?? null,
            // 'parent_id' => $this->parent_id,
        ];
    }
}
