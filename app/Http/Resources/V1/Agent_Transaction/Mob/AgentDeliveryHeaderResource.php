<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentDeliveryHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'delivery_code' => $this->delivery_code,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name,
                'owner_email' => $this->warehouse->owner_email,
                'owner_number' => $this->warehouse->owner_number,
                'address' => $this->warehouse->address
            ] : null,
            // 'route_id' => $this->route_id,
            'route' => $this->route ? [
                'id' => $this->route->id,
                'code' => $this->route->route_code,
                'name' => $this->route->route_name
            ] : null,
            // 'salesman_id' => $this->salesman_id,
            'salesman' => $this->salesman ? [
                'id' => $this->salesman->id,
                'code' => $this->salesman->osa_code,
                'name' => $this->salesman->name
            ] : null,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'code' => $this->customer->osa_code,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'contact_no' => $this->customer->contact_no,
                'town' => $this->customer->town,
                'district' => $this->customer->district,
                'landmark' => $this->customer->landmark
            ] : null,
            'country' => $this->country ? [
                'id' => $this->country->id,
                'code' => $this->country->country_code,
                'name' => $this->country->country_name,
                'currency' => $this->country->currency
            ] : null,
            // 'customer_id' => $this->customer_id,
            // 'currency' => $this->currency,
            // 'country_id' => $this->country_id,
            // 'route_id' => $this->route_id,
            // 'salesman_id' => $this->salesman_id,
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
            'previous_uuid'=> $this->previous_uuid ?? null,
            'next_uuid'    => $this->next_uuid ?? null,
        ];
    }
}
