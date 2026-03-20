<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdvancePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'payment_type' => ['required', 'integer', Rule::in([1, 2, 3])],
            'osa_code' => 'nullable|string|unique:advance_payments,osa_code|max:50',
            'companybank_id' => 'required|integer|exists:tbl_banks,id',
            'agent_id' => 'nullable|integer|exists:tbl_company_customer,id',
            'recipt_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'cheque_no' => 'nullable|string|max:50',
            'cheque_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'recipt_no' => 'required|string|max:50',
            'recipt_date' => 'required|date',
            'status'=> 'required|in:0,1'
        ];

        switch ($this->input('payment_type')) {
            case 1: 
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                $rules['amount'] = 'required';
                $rules['recipt_no'] = 'required';
                $rules['recipt_date'] = 'required';
                $rules['recipt_image'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
                break;

            case 2: 
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                $rules['cheque_no'] = 'required|string|max:50';
                $rules['cheque_date'] = 'required|date';
                $rules['amount'] = 'required';
                $rules['recipt_no'] = 'required';
                $rules['recipt_date'] = 'required';
                $rules['recipt_image'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
                break;

            case 3:
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                $rules['agent_id'] = 'required|integer|exists:tbl_company_customer,id';
                $rules['amount'] = 'required';
                $rules['recipt_no'] = 'required';
                $rules['recipt_date'] = 'required';
                $rules['recipt_image'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_type.in' => 'Payment type must be 1 (Cash), 2 (Cheque), or 3 (Transfer).',
        ];
    }
}
