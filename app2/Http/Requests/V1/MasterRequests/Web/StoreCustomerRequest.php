<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handle permissions if needed
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:20',
            'buyerType' => 'nullable|integer|in:0,1',
            'route_id' => 'nullable|exists:tbl_route,id',
            'customer_category' => 'nullable|exists:customer_categories,id',
            'customer_sub_category' => 'nullable|exists:customer_sub_categories,id',
            'outlet_channel_id' => 'nullable|exists:outlet_channel,id',
            'region_id' => 'nullable|exists:tbl_region,id',
            'area_id' => 'nullable|exists:tbl_areas,id',
            'salesman_id' => 'nullable|exists:salesman,id',
            'fridge_id' => 'nullable|exists:tbl_add_chillers,id',
            'vat_no' => 'nullable|string|max:30',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
