<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                         => $this->id,
            'uuid'                       => $this->uuid,
            'warehouse_code'              => $this->warehouse_code,
            'warehouse_name'              => $this->warehouse_name,
            'owner_name'                  => $this->owner_name,
            'owner_number'                => $this->owner_number,
            'owner_email'                 => $this->owner_email,
            'warehouse_manager'           => $this->warehouse_manager,
            'warehouse_manager_contact'   => $this->warehouse_manager_contact,
            'agreed_stock_capital'=> $this->agreed_stock_capital,
            'warehouse_type'              => $this->warehouse_type,
            'city'                        => $this->city,
            'location' => $this->locationRelation ? [
                'id' => $this->locationRelation->id,
                'code' => $this->locationRelation->code,
                'name' => $this->locationRelation->name
            ] : null,
            'company'                     => $this->getCompany,
            'region' => $this->region ? [
                'id' => $this->region->id,
                'code' => $this->region->region_code,
                'name' => $this->region->region_name
            ] : null,
            'area' => $this->area ? [
                'id' => $this->area->id,
                'code' => $this->area->area_code,
                'name' => $this->area->area_name,
                'region_id' => $this->area->region_id
            ] : null,
            'town_village'                => $this->town_village,
            'street'                      => $this->street,
            'landmark'                    => $this->landmark,
            'latitude'                    => $this->latitude,
            'longitude'                   => $this->longitude,
            'tin_no'                      => $this->tin_no, 
            'p12_file'                    => $this->p12_file,
            'password'                    => $this->password,
            'status'                      => $this->status,
            'is_efris'                    => $this->is_efris,
            'is_branch'                   => $this->is_branch,
            'tin_no'                      => $this->tin_no,
        ];
    }
            // 'registation_no'              => $this->registation_no,
            // 'business_type'               => $this->business_type,
            // 'address'                     => $this->address,
            // 'invoice_sync'                => $this->invoice_sync,
            // 'branch_id'                   => $this->branch_id,

    // 'getCompanyCustomer' => $this->getCompanyCustomer ? [
    //     'id' => $this->getCompanyCustomer->id,
    //     'code' => $this->getCompanyCustomer->customer_code,
    //     'business_name' => $this->getCompanyCustomer->business_name,
    //     'owner_name' => $this->getCompanyCustomer->owner_name
    // ] : null,
    // 'stock_capital'               => $this->stock_capital,
    // 'deposite_amount'             => $this->deposite_amount,
    // 'district'                    => $this->district,
                // 'threshold_radius'            => $this->threshold_radius,
            // 'device_no'                   => $this->device_no,
}
