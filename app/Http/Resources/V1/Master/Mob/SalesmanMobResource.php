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
            // 'username' => $this->username,
            'type' => $this->type,
            'type_name' => $this->salesmanType?->salesman_type_name ?? null,
            'sub_type' => $this->subtype?->id ?? null,
            'Subtype_name' => $this->subtype?->name ?? null,
            'name'     => $this->name ?? null,
            'email'    => $this->email ?? null,
            'contact_no'   => $this->contact_no ?? null,
            'device_no'   => $this->device_no ?? null,
            'route' => $this->type == 2
                ? ($this->load_route ?? null)
                : [
                    'id'   => $this->route_id,
                    'name' => $this->route?->route_name,
                ],
            'block_date_to'   => $this->block_date_to ?? null,
            'block_date_from'   => $this->block_date_from ?? null,
            'warehouses' => $this->warehouses_data ? $this->warehouses_data->map(function ($wh) {
                return [
                    'id' => $wh->id,
                    'code' => $wh->warehouse_code,
                    'name' => $wh->warehouse_name,
                    'location' => $wh->locationRelation?->name,
                    'selling_currency' => $wh->companyRelation?->selling_currency,
                    'purchase_currency' => $wh->companyRelation?->purchase_currency,
                    'tin_no' => $wh->tin_no,
                    'is_efris' => $wh->is_efris,
                    'owner_number' => $wh->owner_number,
                    'warehouse_manager_contact' => $wh->warehouse_manager_contact,
                ];
            }) : [],
            'attendance' => [
            'uuid'     => $this->attendance['uuid'] ?? null,
            'date'     => $this->attendance['date'] ?? null,
            'check_in' => $this->attendance['check_in'] ?? 0,
             ], 
            'device_no'   => $this->device_no ?? null,
            'token_no'   => $this->token_no ?? null,
            'sap_id'   => $this->sap_id ?? null,
            'is_take'   => $this->is_take ?? null,
        ];
    }
}
