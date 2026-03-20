<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // or role-based logic
    }

    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|max:255',
            'customer_type'     => 'sometimes|exists:customer_types,id',
            'warehouse'         => 'sometimes|exists:tbl_warehouse,id',
            'owner_name'        => 'sometimes|string|max:25',
            'route_id'          => 'sometimes|exists:tbl_route,id',
            'landmark'          => 'nullable|string',
            'district'          => 'nullable|string',
            'street'            => 'nullable|string',
            'town'              => 'nullable|string',
            'whatsapp_no'       => 'nullable|string|max:200',
            'contact_no'        => 'sometimes|string|max:20',
            'contact_no2'       => 'sometimes|string|max:20',
            'buyertype'         => 'sometimes|in:0,1',
            'payment_type'      => 'sometimes|in:1,2,3',
            'is_cash'           => 'sometimes|in:0,1',
            'vat_no'            => 'sometimes|string',
            'creditday'         => 'sometimes|numeric',
            'credit_limit'      => 'nullable',
            'outlet_channel_id' => 'sometimes|exists:outlet_channel,id',
            'category_id'       => 'sometimes|exists:customer_categories,id',
            'subcategory_id'    => 'sometimes|exists:customer_sub_categories,id',
            'latitude'          => 'nullable|string',
            'longitude'         => 'nullable|string',
            'qr_code'           => 'nullable|string',
            'status'            => 'sometimes|integer|in:0,1',
            'enable_promotion'  => 'sometimes|integer|in:0,1',
        ];
    }
}
