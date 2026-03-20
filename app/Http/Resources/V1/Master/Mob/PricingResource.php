<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class PricingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'name'                  => $this->name,
            'code'                  => $this->code,
            'description'           => $this->description,
            'start_date'            => $this->start_date,
            'end_date'              => $this->end_date,
            'apply_on'              => $this->apply_on,
            'warehouse_id'          => $this->warehouse_id,
            'item_type'             => $this->item_type,
            'status'                => $this->status,
            'company_id'            => $this->company_id,
            'region_id'             => $this->region_id,
            'area_id'               => $this->area_id,
            'route_id'              => $this->route_id,
            'item_id'               => $this->item_id,
            'item_category_id'      => $this->item_category_id,

            'customer_id'           => $this->customer_id,
            'customer_category_id'  => $this->customer_category_id,
            'customer_type_id'      => $this->customer_type_id,

            'outlet_channel_id'     => $this->outlet_channel_id,
            'Details' => $this->details->map(function ($d) {
                return [
                    'id'        => $d->id,
                    'name'      => $d->name,
                    'item_id'  => $d->item_id,
                    'buom_ctn_price'=> $d->buom_ctn_price,
                    'auom_pc_price'=> $d->auom_pc_price,
                    'status'    => $d->status,
                ];
            }),
        ];
    }
}
