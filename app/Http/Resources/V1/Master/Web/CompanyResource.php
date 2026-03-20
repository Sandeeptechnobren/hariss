<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray($request): array
    {
    return [
        'id'                => $this->id,
        'company_code'      => $this->company_code,
        'company_name'      => $this->company_name,
        'email'             => $this->email,
        'tin_number'        => $this->tin_number,
        'vat'               => $this->vat,
        'country' => $this->country ? [
            'id' => $this->country->id,
            'country_name' => $this->country->country_name,
            'country_code' => $this->country->country_code,
            'selling_currency'=>$this->country->currency,
            'purchase_currency'=>$this->country->currency,
        ] : null,
        'selling_currency'  => $this->selling_currency,
        'purchase_currency' => $this->purchase_currency,
        'toll_free_no'      => $this->toll_free_no,
        'logo'              => $this->logo,
        'website'           => $this->website,
        'service_type'      => $this->service_type,
        'company_type'      => $this->company_type,
        'status'            => $this->status,
        'module_access'     => $this->module_access,
        'city'          => $this->city,
        'address'              => $this->address,
        // 'street'            => $this->street,
        // 'landmark'          => $this->landmark,
        'region' => $this->getregion ? [
            'id' => $this->getregion->id,
            'region_name' => $this->getregion->region_name,
            'region_code' => $this->getregion->region_code,
        ] : null,
        'sub_region'        => $this->getarea?[
            'id'=>$this->getarea->id,
            'subregion_name'=>$this->getarea->area_name,
            'subregion_code'=>$this->getarea->area_code,
        ]:null,
        'primary_contact'   => $this->primary_contact,
        
    ];

    }
}
