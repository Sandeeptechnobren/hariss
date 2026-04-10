<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;

class NewCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'osa_code' => 'nullable|string|max:50',
            'name' => 'required|string',
            'email' => 'nullable|string|max:255',
            'language'=> 'nullable|string|max:155',
            'contact_no' => 'required|string|max:20',
            'customer_type' => 'required|integer|exists:customer_types,id',
            'route_id' => 'required|integer|exists:tbl_route,id',
            'warehouse' => 'required|integer|exists:tbl_warehouse,id',
            'salesman_id' => 'required|integer|exists:salesman,id', 

            'is_whatsapp' => 'nullable|integer|in:0,1',
            'whatsapp_no' => 'nullable|string|max:20',
            'contact_no2' => 'nullable|string|max:20',
            'owner_name' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'payment_type' => 'nullable|string|max:100',
            'creditday' => 'nullable|integer',
            'vat_no' => 'nullable|string|max:50',

            'outlet_channel_id' => 'nullable|integer|exists:outlet_channel,id',
            'category_id' => 'nullable|integer|exists:customer_categories,id',
            'subcategory_id' => 'nullable|integer|exists:customer_sub_categories,id',
            'avelable_fridge' => 'nullable|string|max:255',
            'visit_on'      => 'nullable|string|max:255',
             
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'credit_limit' => 'nullable|numeric',
            'status' => 'nullable|integer|in:0,1,2',
            'approval_status' => 'nullable|integer|in:3,1,2',// 1=Approved, 2=Pending, 3=Rejected
            // 'reject_reason' => 'nullable|string|max:255',
        ];

        // For update, make all optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $key => &$rule) {
                $rule = str_replace('required|', '', $rule);
            }
        }

        return $rules;
    }
public function messages()
    {
        return [
            'name.required' => 'Customer name is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',

            'contact_no.required' => 'Contact number is required.',
            'contact_no.max' => 'Contact number must not exceed 20 digits.',

            'customer_type.required' => 'Customer type is required.',
            'customer_type.exists' => 'Selected customer type is invalid.',

            'route_id.required' => 'Route is required.',
            'route_id.exists' => 'Selected route is invalid.',

            'warehouse.required' => 'Warehouse is required.',
            'warehouse.exists' => 'Selected warehouse is invalid.',

            'salesman_id.required' => 'Salesman is required.',
            'salesman_id.exists' => 'Selected salesman is invalid.',

            'is_whatsapp.in' => 'WhatsApp status must be 0 or 1.',

            'approval_status.in' => 'Approval status must be Approved, Pending, or Rejected.',

            'longitude.numeric' => 'Longitude must be a valid number.',
            'latitude.numeric' => 'Latitude must be a valid number.',
        ];
    }
}
