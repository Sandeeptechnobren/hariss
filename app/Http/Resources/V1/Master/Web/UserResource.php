<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'name'              => $this->name,
            'email'             => $this->email,
            'username'          => $this->username,
            'contact_number'    => $this->contact_number,
            'profile_picture'   => $this->profile_picture,
            'role'              => [
                'id'    => optional($this->roleDetails)->id,
                'name'  => optional($this->roleDetails)->name,
            ],
            'country_id'              => [
                'id'    => optional($this->countryId)->id,
                'name'  => optional($this->countryId)->country_name,
                'code'  => optional($this->countryId)->country_code,
            ],
            'status'            => $this->status,
            'street'            => $this->street,
            'city'            => $this->city,
            'zip'            => $this->zip,
            'dob'            => $this->dob,
            // 'country_id'            => $this->country_id,
            'companies'         => $this->getCompaniesFull(),
            'warehouses'        => $this->getWarehousesFull(),
            'item'        => $this->getitem(),
            'routes'            => $this->getRoutesFull(),
            'salesmen'          => $this->getSalesmenFull(),
            'regions'           => $this->getRegionsFull(),
            'areas'             => $this->getAreasFull(),
            'outlet_channels'   => $this->getOutletChannelsFull(),
            'company' => $this->whenLoaded('getCompanyDetails', function () {
                return [
                    'id'            => $this->getCompanyDetails->id,
                    'company_code'  => $this->getCompanyDetails->company_code ?? null,
                    'company_name'  => $this->getCompanyDetails->company_name ?? null,
                ];
            }),
            // 'company_ids'       => $this->company,
            // 'warehouse_ids'     => $this->warehouse,
            // 'route_ids'         => $this->route,
            // 'salesman_ids'      => $this->salesman,
            // 'region_ids'        => $this->region,
            // 'area_ids'          => $this->area,
            // 'outlet_channel_ids'=> $this->outlet_channel,
        ];
    }
}
