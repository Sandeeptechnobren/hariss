<?php

namespace App\Http\Resources\V1\Loyality_Management;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'osa_code'       => $this->osa_code,
            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,
            'route_id'       => $this->route_id,
            'route_code'     => $this->route->route_code ?? null,
            'route_name'     => $this->route->route_name ?? null,
            'customer_id'    => $this->customer_id,
            'customer_code'  => $this->customer->osa_code ?? null,
            'customer_name'  => $this->customer->name ?? null,
            'currentreward_points' => $this->currentreward_points,
            'adjustment_points'    => $this->adjustment_points, 
            'closing_points'       => $this->closing_points,
            'adjustment_symbol'    => $this->adjustment_symbol,
            'description'          => $this->description,
        ]; 
    }

}