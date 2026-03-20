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
            'sap_code' => [
                'required',
                'string',
                'max:200',
            ],
            'customer_code' => [
                'required',
                'string',
                'max:200',
            ],
            'business_name' => 'nullable|string|max:50',
            'customer_type' => 'required',
            'owner_name' => 'required|string|max:200',
            'owner_no' => 'required|string',
            'is_whatsapp' => 'nullable|in:0,1',
            'whatsapp_no' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'language' => 'required|string|max:20',
            'contact_no2' => 'nullable|string|max:20',
            'buyerType' => 'nullable|in:0,1',
            'road_street' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric',
            'payment_type' => 'nullable|in:0,1,2,3',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'creditday' => 'required|string|max:255',
            'tin_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tbl_company_customer', 'tin_no')->ignore((int) $id),
            ],
            'accuracy' => 'nullable|string|max:50',
            'creditlimit' => 'nullable|numeric',
            'guarantee_name' => 'required|string|max:500',
            'guarantee_amount' => 'required|numeric',
            'guarantee_from' => 'required|date',
            'guarantee_to' => 'required|date',
            'totalcreditlimit' => 'required|numeric',
            'credit_limit_validity' => 'nullable|date',
            'region_id' => 'required|integer',
            'area_id' => 'required|integer',
            'vat_no' => 'required|string|max:30',
            'longitude' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'threshold_radius' => 'required|integer',
            'dchannel_id' => 'required|integer',
            'status' => 'nullable|in:0,1'
        ];
    }
}
