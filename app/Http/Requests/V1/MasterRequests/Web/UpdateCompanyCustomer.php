<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyCustomer extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'company_type'=>'nullable|integer',
            'sap_code' => [
                'nullable',
                'string',
                'max:200',
            ],
            'osa_code' => [
                'nullable',
                'string',
                'max:200',
            ],
            'business_name' => 'nullable|string|max:50',
            // 'customer_type' => 'required|in:2,4',
            'language' => 'nullable|string|max:20',
            'landmark' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'payment_type' => 'nullable|in:0,1,2,3',
            'creditday' => 'nullable|string|max:255',
            'tin_no' => [
                'nullable',
                'string',
                'max:255',
            ],
            'creditlimit' => 'nullable|numeric',
            'bank_guarantee_name' => 'nullable|string|max:500',
            'bank_guarantee_amount' => 'nullable|numeric',
            'bank_guarantee_from' => 'nullable|date',
            'bank_guarantee_to' => 'nullable|date',
            'totalcreditlimit' => 'nullable|numeric',
            'credit_limit_validity' => 'nullable|date',
            'region_id' => 'nullable|integer',
            'distribution_channel_id' => 'nullable|integer',
            'status' => 'nullable|in:0,1',
            'business_type' => 'nullable|integer|in:1,0',
            'contact_number' => 'string|nullable',
            'area_id' => 'nullable|integer'
        ];
    }
}
