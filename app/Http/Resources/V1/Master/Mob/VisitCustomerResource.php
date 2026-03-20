<?php
namespace App\Http\Resources\V1\Master\Mob;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitCustomerResource extends JsonResource
{
    public function toArray($request)
{
    $customer = $this['customer_details'];

     return [

        'customer_id' => $customer->id ?? null,
        // 'uuid' => $customer->uuid ?? null,

        'sap_code' => $customer->sap_code ?? null,
        'osa_code' => $customer->osa_code ?? null,

        'customer_type' => $customer->customer_type ?? null,

        'route_id' => $customer->route_id ?? null,

        'customer_name' => $customer->name ?? $customer->business_name ?? null,

        'business_type' => $customer->business_type ?? null,
        'company_type' => $customer->company_type ?? null,

        'owner_name' => $customer->owner_name ?? null,

        'contact_number' => $customer->contact_no ?? $customer->contact_number ?? null,
        'contact_number2' => $customer->contact_no2 ?? null,
        'whatsapp_number' => $customer->whatsapp_no ?? null,

        'street' => $customer->street ?? null,
        'town' => $customer->town ?? null,
        'landmark' => $customer->landmark ?? null,
        'district' => $customer->district ?? null,

        'warehouse' => $customer->warehouse ?? null,

        'category_id' => $customer->category_id ?? null,
        'subcategory_id' => $customer->subcategory_id ?? null,
        'outlet_channel_id' => $customer->outlet_channel_id ?? null,

        'region_id' => $customer->region_id ?? null,
        'area_id' => $customer->area_id ?? null,
        'distribution_channel_id' => $customer->distribution_channel_id ?? null,

        'credit_day' => $customer->creditday ?? null,

        'credit_limit' => $customer->credit_limit ?? $customer->creditlimit ?? null,
        'total_credit_limit' => $customer->totalcreditlimit ?? null,
        'credit_limit_validity' => $customer->credit_limit_validity ?? null,

        'payment_type' => (int) $customer->payment_type,

        'buyer_type' => $customer->buyertype ?? null,

        'enable_promotion' => $customer->enable_promotion ?? null,

        'is_cash' => $customer->is_cash ?? null,

        'vat_no' => $customer->vat_no ?? $customer->tin_no ?? null,

        'qr_code' => $customer->qr_code ?? null,

        'latitude' => $customer->latitude ?? null,
        'longitude' => $customer->longitude ?? null,

        'language' => $customer->language ?? null,
        'email' => $customer->email ?? null,

        'tier' => $customer->Tier ?? null,

        'bank_guarantee_name' => $customer->bank_guarantee_name ?? null,
        'bank_guarantee_amount' => $customer->bank_guarantee_amount ?? null,
        'bank_guarantee_from' => $customer->bank_guarantee_from ?? null,
        'bank_guarantee_to' => $customer->bank_guarantee_to ?? null,

        'bank_account_number' => $customer->bank_account_number ?? null,
        'bank_name' => $customer->bank_name ?? null,

        'merchandiser_id' => $customer->merchandiser_id ?? null,

        'status' => $customer->status ?? null,

        'is_sequence' => $this->is_sequence ?? 0,

        'fridge_details' => method_exists($customer, 'fridgeStatus')
            ? $customer->fridgeStatus?->map(fn($fridge) => [
                'id' => $fridge->fridge_id,
                'serial_number' => $fridge->chiller?->serial_number,
            ]) ?? collect()
            : collect(),
    ];
}
}
