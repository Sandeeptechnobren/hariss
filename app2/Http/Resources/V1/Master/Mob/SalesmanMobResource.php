<?php

namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesmanMobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
       return [
            'id'       => $this->id,
            'osa_code' => $this->osa_code,
            'username' => $this->username,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'name'     => $this->name ?? null,
            'email'    => $this->email ?? null,
            'contact_no'   => $this->contact_no ?? null,
            'device_no'   => $this->device_no ?? null,
            'route_id'   => $this->route_id ?? null,
            'block_date_to'   => $this->block_date_to ?? null,
            'block_date_from'   => $this->block_date_from ?? null,
            'warehouse_id'   => $this->warehouse_id ?? null,
            'token_no'   => $this->token_no ?? null,
            'sap_id'   => $this->sap_id ?? null,
            'email'   => $this->email ?? null,
        ];
    }
}
