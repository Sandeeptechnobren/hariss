<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CompanyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

public function rules(): array
    {
        return [
            'company_code'      => 'nullable|string',
            'company_name'      => 'nullable|string|max:255',
            'email'             => 'nullable|email',
            'tin_number'        => 'nullable|string',
            'vat'               => 'nullable|string',
            'country_id'        => 'nullable|exists:tbl_country,id',
            'selling_currency'  => 'nullable|string|max:5',
            'purchase_currency' => 'nullable|string|max:5',
            'toll_free_no'      => 'nullable|string',
            'logo'              => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'website'           => 'nullable|url',
            'service_type'      => 'nullable|in:branch,warehouse',
            'company_type'      => 'nullable|in:trading,manufacturing',
            'status'            => 'nullable|in:0,1,2',
            'module_access' => 'nullable|string',
            // 'module_access.inventory' => 'boolean',
            // 'module_access.sales' => 'boolean',
            'city'          => 'nullable|string|max:255',
            'address'              => 'nullable|string|max:255',
            // 'street'            => 'required|string|max:255',
            // 'landmark'          => 'nullable|string|max:255',
            // 'region'            => 'nullable|exists:tbl_region,id',
            // 'sub_region'        => 'nullable|exists:tbl_areas,id',
            'primary_contact'   => 'nullable|string|max:255',
        ];
    }

}