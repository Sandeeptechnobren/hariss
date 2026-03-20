<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CapsCollectionHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'code'           => $this->code,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,

            'route_id'       => $this->route_id,
            'route_code'     => $this->route->route_code ?? null,
            'route_name'     => $this->route->route_name ?? null,

            'salesman_id'    => $this->salesman_id,
            'salesman_code'  => $this->salesman->osa_code ?? null,
            'salesman_name'  => $this->salesman->name ?? null,
            'customer_id'    => $this->customer,
            'customer_code'  => $this->customerdata->osa_code ?? null,
            'customer_name'  => $this->customerdata->name ?? null,

            'status'         => $this->status,
            'latitude'         => $this->latitude,
            'longitude'         => $this->longitude,
            'details'        => CapsCollectionDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}