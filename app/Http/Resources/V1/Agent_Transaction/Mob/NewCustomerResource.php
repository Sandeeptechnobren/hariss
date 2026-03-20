<?php

namespace app\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class NewCustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'osa_code'        => $this->osa_code,
            'name'            => $this->name,
            'owner_name'      => $this->owner_name,
            'contact_no'      => $this->contact_no,
            'contact_no2'     => $this->contact_no2,
            'whatsapp_no'     => $this->whatsapp_no,
            'is_whatsapp'     => $this->is_whatsapp,
            'town'            => $this->town,
            'street'          => $this->street,
            'landmark'        => $this->landmark,
            'district'        => $this->district,
            'longitude'       => $this->longitude,
            'latitude'        => $this->latitude,
            'credit_limit'    => $this->credit_limit,
            'creditday'       => $this->creditday,
            'vat_no'          => $this->vat_no,
            'payment_type'    => $this->payment_type,
            'approval_status' => $this->approval_status,
            'reject_reason'   => $this->reject_reason,
            'status'          => $this->status,
            // 'customer_id'     => $this->agentCustomer,
            'customer_type'   => $this->customer_type,
            'outlet_channel'  => $this->outlet_channel?->id,
            'category'        => $this->category,
            'subcategory'     => $this->subcategory,
            'route'           => $this->route?->route_name,
            'warehouse'       => $this->getWarehouse?->warehouse_name,
            'created_at'      => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'      => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
