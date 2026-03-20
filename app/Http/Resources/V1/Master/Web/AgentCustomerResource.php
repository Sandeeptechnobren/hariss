<?php
namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentCustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'name' => $this->name,
            'owner_name' => $this->owner_name,
            'customer_type' => $this->customertype?[
                'id'=>$this->customertype->id,
                'code'=>$this->customertype->code,
                'name'=>$this->customertype->name,
            ]:null,
            'vat_no'=>$this->vat_no,
            'route' => $this->route ? [
                'id' => $this->route->id,
                'route_code' => $this->route->route_code,
                'route_name' => $this->route->route_name,
            ] : null,
            // 'region' => $this->region ? [
            //     'id' => $this->region->id,
            //     'region_code' => $this->region->region_code,
            //     'region_name' => $this->region->region_name,
            // ] : null,
            // 'area' => $this->area ? [
            //     'id' => $this->area->id,
            //     'area_code' => $this->area->area_code,
            //     'area_name' => $this->area->area_name,
            // ] : null,
            'outlet_channel' => $this->outlet_channel ? [
                'id' => $this->outlet_channel->id,
                'outlet_channel_code' => $this->outlet_channel->outlet_channel_code,
                'outlet_channel' => $this->outlet_channel->outlet_channel,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'customer_category_code' => $this->category->customer_category_code,
                'customer_category_name' => $this->category->customer_category_name,
            ] : null,
            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                // 'customer_category_id' => $this->subcategory->customer_category_id,
                'customer_sub_category_name'=>$this->subcategory->customer_sub_category_name, 
                'customer_sub_category_code' => $this->subcategory->customer_sub_category_code,
            ] : null,
            'getWarehouse' => $this->getWarehouse ? [
                'id' => $this->getWarehouse->id,
                'warehouse_code' => $this->getWarehouse->warehouse_code,
                'warehouse_name' => $this->getWarehouse->warehouse_name,
                'vehicle' => $this->getWarehouse->vehicle_id,
            ] : null,
            // Flat fields
            'route_id' => $this->route_id,
            'landmark' => $this->landmark,
            'district' => $this->district,
            'street' => $this->street,
            'town' => $this->town,
            'whatsapp_no' => $this->whatsapp_no,
            'contact_no'=>$this->contact_no,
            'contact_no2' => $this->contact_no2,
            'payment_type' => $this->payment_type,
            'creditday' => $this->creditday,
            'credit_limit' => $this->credit_limit,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status
        ];
    }
}
