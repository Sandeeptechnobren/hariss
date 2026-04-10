<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentDeliveryHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'delivery_code' => $this->delivery_code,
            'warehouse' => $this->warehouse_id,
            'route_id' => $this->route_id,
            'salesman_id' => $this->salesman_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'country_id' => $this->country_id,
            'gross_total' => $this->gross_total,
            'vat' => $this->vat,
            'discount' => $this->discount,
            'net_amount' => $this->net_amount,
            'total' => $this->total,
            'delivery_date' => $this->delivery_date,
            'comment' => $this->comment,
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'details' => AgentDeliveryDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
