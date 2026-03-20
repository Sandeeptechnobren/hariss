<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'company_type'=>'required|integer',
            'sap_code' => [
                'required',
                'string',
                'max:200',
            ],
            'osa_code' => [
                'required',
                'string',
                'max:200',
            ],
            'business_name' => 'required|string|max:50',
            // 'customer_type' => 'required|in:2,4',
            'language' => 'required|string|max:20',
            'landmark' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'payment_type' => 'nullable|in:0,1,2,3',
            'creditday' => 'nullable|string|max:255',
            'tin_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tbl_company_customer', 'tin_no')->ignore((int) $id),
            ],
            'creditlimit' => 'nullable|numeric',
            'bank_guarantee_name' => 'required|string|max:500',
            'bank_guarantee_amount' => 'required|numeric',
            'bank_guarantee_from' => 'required|date',
            'bank_guarantee_to' => 'required|date',
            'totalcreditlimit' => 'required|numeric',
            'credit_limit_validity' => 'nullable|date',
            'region_id' => 'required|integer',
            'distribution_channel_id' => 'required|integer',
            'status' => 'required|in:0,1',
            'business_type' => 'required|integer|in:1,0',
            'contact_number' => 'string|nullable',
            'area_id' => 'required|integer'
        ];
    }
}
