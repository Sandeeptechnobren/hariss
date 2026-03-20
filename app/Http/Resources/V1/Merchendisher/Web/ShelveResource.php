<?php

namespace App\Http\Resources\V1\Merchendisher\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CompanyCustomer;
use App\Models\Salesman;

class ShelveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
    return [
        'id' => $this->id,
        'uuid' => $this->uuid,
        'code' => $this->code,
        'shelf_name' => $this->shelf_name,
        'height' => $this->height,
        'width' => $this->width,
        'depth' => $this->depth,
        'valid_from' => $this->valid_from,
        'valid_to' => $this->valid_to,
        'customer_ids' => $this->customer_ids,
        'customers' => $this->customers->map(function ($customer) {
            return [
                'customers'=> $customer->id,
                'customer_code' => $customer->osa_code,
                'customer_type' => $customer->customer_type,
                'owner_name' => $customer->business_name,
            ];
        }),
        'merchendiser_ids' => $this->merchendiser_ids,
        'merchandisers' => $this->merchandisers->map(function ($merch) {
            return [
                'merchandisers'=> $merch->id,
                'osa_code' => $merch->osa_code,
                'type' => $merch->type,
                'name' => $merch->name,
            ];
        }),
        'created_by' => $this->created_user,
        'created_at' => $this->created_at
    ];
    }
}   