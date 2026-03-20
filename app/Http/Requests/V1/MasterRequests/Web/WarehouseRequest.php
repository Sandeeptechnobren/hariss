<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_code' => 'nullable|alpha_num|max:20|unique:tbl_warehouse,warehouse_code',
            'warehouse_type' => 'required|string|max:75',
            'warehouse_name' => 'required|string|min:3|max:50',
            'owner_name' => 'required|string|max:50',
            'owner_number' => 'nullable|numeric|digits_between:1,15',
            'owner_email' => 'nullable|email|max:50',
            'agreed_stock_capital'=>'required',
            'location' => 'required|string|max:50',
            'city' => 'required|string|max:25',
            'warehouse_manager' => 'required|string|max:50',
            'warehouse_manager_contact' => 'nullable|numeric|digits_between:1,20',
            'tin_no'=>'nullable',
            'company'=>'required|exists:tbl_company,id',
            'warehouse_email'=>'nullable|email|max:50',
            // 'tin_no' => 'nullable|string|max:30',
            // 'registation_no' => 'nullable|string|max:30',
            // 'business_type' => 'nullable|in:0,1',
            // 'warehouse_type' => 'required|in:0,1,2',
            'city' => 'required|string|max:25',
            'location' => 'required|string|max:50',
            // 'address' => 'required|string|max:100',
            // 'stock_capital' => 'nullable|numeric',
            // 'deposite_amount' => 'nullable|numeric',
            'region_id' => 'nullable|exists:tbl_region,id',
            'area_id' => 'nullable|exists:tbl_areas,id',
            'latitude' => [
                'required',
                'string',
                'max:50',
                'regex:/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/'
            ],
            'longitude' => [
                'required',
                'string',
                'max:50',
                'regex:/^[-+]?((1[0-7]\d|[0-9]?\d)(\.\d+)?|180(\.0+)?)$/'
            ],
            'agent_customer' => 'nullable', 
            'town_village' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:50',
            'is_efris' => 'required|in:0,1',
            'p12_file' => 'nullable|file|mimes:p12',
            'password' => 'required|string|max:100',
            'is_branch' => 'required|in:0,1',
            // 'is_efris' => 'nullable|in:0,1',
            'device_no' => 'nullable|string|max:100',
            // 'p12_file' => 'required|string|max:100',
            'password' => 'nullable|string|max:100',
            // 'is_branch' => 'nullable|in:0,1',
            'branch_id' => 'nullable|integer|max:100',

            // 'company_customer_id' => 'nullable',
            // 'tin_no' => 'nullable|string|max:30',
            // 'registation_no' => 'nullable|string|max:30',
            // 'business_type' => 'nullable|in:0,1',
            // 'warehouse_type' => 'required',
            // 'address' => 'required|string|max:100',
            // 'stock_capital' => 'nullable|numeric',
            // 'deposite_amount' => 'nullable|numeric',
            // 'sub_region_id' => 'nullable|exists:tbl_sub_region,id',
            // 'district' => 'nullable|string|max:50',
            // 'threshold_radius' => 'nullable|numeric|min:0',
            // 'device_no' => 'nullable|string|max:100',
            // 'invoice_sync' => 'nullable|in:0,1',
            'status' => 'required|integer|in:0,1',
            'created_user' => 'nullable|exists:users,id',
            'updated_user' => 'nullable|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_code.alpha_num' => 'Warehouse code must be alphanumeric',
            'warehouse_name.required' => 'Warehouse name is required',
            'warehouse_name.min' => 'Warehouse name must be at least 3 characters',
            // 'company_customer_id.exists' => 'Selected company customer does not exist',
            'region_id.exists' => 'Selected region does not exist',
            // 'sub_region_id.exists' => 'Selected sub-region does not exist',
            'area_id.exists' => 'Selected area does not exist',
            // 'tin_no.unique' => 'TIN number already exists',
            // 'registation_no.unique' => 'Registration number already exists',
            'latitude.regex' => 'Invalid latitude format (valid range: -90 to +90)',
            'longitude.regex' => 'Invalid longitude format (valid range: -180 to +180)',
            // 'business_type.in' => 'Business type must be 0 (B2C) or 1 (B2B)',
            'warehouse_type.in' => 'Warehouse type must be 0 (Agent), 1 (Hariss), or 2 (Outlet)',
            // 'threshold_radius.numeric' => 'Threshold radius must be a valid number',
        ];
    }
}
