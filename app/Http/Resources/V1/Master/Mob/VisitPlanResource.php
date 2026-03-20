<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitPlanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'salesman_id'      => $this->salesman_id,
            'customer_id'      => $this->customer_id,
            'warehouse_id'     => $this->warehouse_id,
            'route_id'         => $this->route_id,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'visit_start_time' => $this->visit_start_time,
            'visit_end_time'   => $this->visit_end_time,
            'shop_status'      => $this->shop_status,
            'remark'           => $this->remark,
        ];
    }
}
