<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvancePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'payment_type' => 'nullable|in:1,2,3',
            'osa_code' => 'nullable|string|max:100',
        ];

        switch ($this->payment_type) {
            case 1: 
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                if ($this->companybank_id) {
                    $rules = array_merge($rules, [
                        'amount' => 'required|numeric|min:0',
                        'recipt_no' => 'required|string|max:50',
                        'recipt_date' => 'required|date',
                        'recipt_image' => 'required|file|mimes:jpg,png,pdf|max:2048',
                    ]);
                }
                break;

            case 2:
                $rules['cheque_no'] = 'required|string|max:50';
                $rules['cheque_date'] = 'required|date';
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                if ($this->companybank_id) {
                    $rules = array_merge($rules, [
                        'amount' => 'nullable|numeric|min:0',
                        'recipt_no' => 'nullable|string|max:50',
                        'recipt_date' => 'nullable|date',
                        'recipt_image' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
                    ]);
                }
                break;

            case 3:
                $rules['agent_id'] = 'required|integer|exists:tbl_company_customer,id';
                $rules['companybank_id'] = 'required|integer|exists:tbl_banks,id';
                if ($this->companybank_id) {
                    $rules = array_merge($rules, [
                        'amount' => 'nullable|numeric|min:0',
                        'recipt_no' => 'nullable|string|max:50',
                        'recipt_date' => 'nullable|date',
                        'recipt_image' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
                    ]);
                }
                break;
        }

        return $rules;
    }
}
