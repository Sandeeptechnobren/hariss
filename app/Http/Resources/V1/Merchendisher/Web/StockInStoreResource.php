<?php

namespace App\Http\Resources\V1\Merchendisher\Web;
use App\Models\CompanyCustomer;
use Illuminate\Http\Resources\Json\JsonResource;

class StockInStoreResource extends JsonResource
{
   public function toArray($request)
{
    return [
        'id'              => $this->id,
        'code'            => $this->code,
        'uuid'            => $this->uuid,
        'activity_name'   => $this->activity_name,
        'date_range'      => [
            'from' => $this->date_from ? $this->date_from->format('Y-m-d') : null,
            'to'   => $this->date_to ? $this->date_to->format('Y-m-d') : null,
        ],
        'assign_customers' => CompanyCustomer::whereIn(
            'id',
            $this->assign_customers ?? []
        )->get()->map(fn ($customer) => [
            'id'   => $customer->id,
            'name' => $customer->business_name,
            'code' => $customer->osa_code,
        ]),
        'inventories' => AsignInventoryResource::collection($this->whenLoaded('inventories')),        
    ];
}
}