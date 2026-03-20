<?php

namespace App\Http\Resources\V1\Master\Web;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'name' => $this->name,
            'email' => $this->email,
            'owner_name' => $this->owner_name,
            'region' => $this->region ? [
                'id' => $this->region->id,
                'name' => $this->region->region_name,
                'code' => $this->region->region_code,
            ] : null,

            'route' => $this->route ? [
                'id' => $this->route->id,
                'code' => $this->route->route_code,
                'name' => $this->route->route_name,
            ] : null,

            'area' => $this->area ? [
                'id' => $this->area->id,
                'name' => $this->area->area_name,
                'code' => $this->area->area_code,
            ] : null,

            'salesman' => $this->salesman ? [
                'id' => $this->salesman->id,
                'code' => $this->salesman->osa_code,
                'name' => $this->salesman->name,
            ] : null,


            'fridge' => $this->fridgeRelation ? [
                'id' => $this->fridgeRelation->id,
                'code' => $this->fridgeRelation->fridge_code,
            ] : null,

            'customer_type' => $this->customerTypeRelation ? [
                'id' => $this->customerTypeRelation->id,
                'name' => $this->customerTypeRelation->name,
                'code' => $this->customerTypeRelation->code,
            ] : null,
            'customerCategory' => $this->customerCategory ? [
                'id' => $this->customerCategory->id,
                'code' => $this->customerCategory->customer_category_code,
                'name' => $this->customerCategory->customer_category_name,
            ] : null,

            'customerSubCategory' => $this->customerSubCategory ? [
                'id' => $this->customerSubCategory->id,
                'code' => $this->customerSubCategory->customer_sub_category_code,
                'name' => $this->customerSubCategory->customer_sub_category_name,
            ] : null,

            'outletChannel' => $this->outletChannel ? [
                'id' => $this->outletChannel->id,
                'code' => $this->outletChannel->outlet_channel_code,
                'name' => $this->outletChannel->outlet_channel,
            ] : null,

            // Direct attributes
            'street' => $this->street,
            'customersequence' => $this->customersequence,
            'language' => $this->language,
            'buyerType' => $this->buyerType,
            'ura_address' => $this->ura_address,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'phone_1' => $this->phone_1,
            'phone_2' => $this->phone_2,
            'city' => $this->city,
            'state' => $this->state,
            'balance' => $this->balance,
            'pricingkey' => $this->pricingkey,
            'promotionkey' => $this->promotionkey,
            'authorizeditemgrpkey' => $this->authorizeditemgrpkey,
            'paymentmethod' => $this->paymentmethod,
            'payment_type' => $this->payment_type,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'creditday' => $this->creditday,
            'status' => $this->status,
            'vat_no' => $this->vat_no,
        ];
    }
}
