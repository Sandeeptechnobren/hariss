<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('id');

        return [
            'osa_code' => [
                'sometimes',
                'string',
                'max:200',
                Rule::unique('new_customer', 'osa_code')->ignore($customerId),
            ],
            'uuid' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'customer_type' => 'required|exists:customer_types,id',
            'warehouse' => 'required|exists:tbl_warehouse,id',
            'owner_name' => 'required|string|max:25',
            'route_id' => 'required|exists:tbl_route,id',
            'landmark' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:100',
            'town' => 'nullable|string|max:100',
            'whatsapp_no' => 'nullable|string|max:11',
            'contact_no' => 'nullable|string|max:20',
            'contact_no2' => 'nullable|string|max:20',
            'buyertype' => 'nullable|in:0,1',
            'payment_type' => 'nullable|in:1,2,3',
            'is_cash' => 'nullable|in:0,1',
            'vat_no' => 'nullable|string|max:50',
            'creditday' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric',
            'outlet_channel_id' => 'required|exists:outlet_channel,id',
            'category_id' => 'required|exists:customer_categories,id',
            'subcategory_id' => 'required|exists:customer_sub_categories,id',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'qr_code' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
            'enable_promotion' => 'nullable|integer|in:0,1',

            // Newly added fields
            'approval_status' => 'nullable|in:1,2,3', // 1=Approved, 2=Pending, 3=Rejected
            'reject_reason' => 'nullable|string|max:500',

            'customer_id' => 'nullable|integer'
        ];
    }
}
