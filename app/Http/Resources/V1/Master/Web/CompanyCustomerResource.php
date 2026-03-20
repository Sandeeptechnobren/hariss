<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyCustomerResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'sap_code'                  => (string) $this->sap_code,
            'osa_code'                  => (string) $this->osa_code,
            'business_name'             => (string) $this->business_name,
            'company_type'              => $this->company_type,
            'company_name'              => (string) ($this->companyType?->name ?? ''),
            'company_code'              => (string) ($this->companyType?->code ?? ''),
            'language'                  => (string) $this->language,
            'town'                      => (string) $this->town,
            'landmark'                  => (string) $this->landmark,
            'district'                  => (string) $this->district,
            'payment_type'              => (string) $this->payment_type,
            'creditday'                 => (string) $this->creditday,
            'tin_no'                    => (string) $this->tin_no,
            'creditlimit'               => (string) $this->creditlimit,
            'bank_guarantee_name'       => (string) $this->bank_guarantee_name,
            'bank_guarantee_amount'     => (string) $this->bank_guarantee_amount,
            'bank_guarantee_from'       => (string) $this->bank_guarantee_from,
            'bank_guarantee_to'         => (string) $this->bank_guarantee_to,
            'totalcreditlimit'          => (string) $this->totalcreditlimit,
            'credit_limit_validity'     => (string) $this->credit_limit_validity,
            'region_id'                 => $this->region_id,
            'area_id'                   => $this->area_id,
            'distribution_channel_id'   => $this->distribution_channel_id,
            'status'                    => $this->status,
            'business_type'             => $this->business_type,
            'contact_number'            => (string) $this->contact_number,
            'created_user'              => $this->created_user,
            'updated_user'              => $this->updated_user,

            'get_region' => $this->whenLoaded('getRegion', function () {
                return [
                    'id'           => $this->getRegion->id,
                    'region_code'  => $this->getRegion->region_code,
                    'region_name'  => $this->getRegion->region_name,
                ];
            }),

            'get_area' => $this->whenLoaded('getArea', function () {
                return [
                    'id'         => $this->getArea->id,
                    'area_code'  => $this->getArea->area_code,
                    'area_name'  => $this->getArea->area_name,
                ];
            }),
        ];
    }
}
