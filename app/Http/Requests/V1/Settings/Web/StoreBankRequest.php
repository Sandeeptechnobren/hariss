<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
         $bankId = $this->route('bank') ?? 'NULL';

        return [
            'osa_code' => 'nullable|string|max:50|unique:tbl_banks,osa_code,' . $bankId,
            'bank_name' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'account_number' => 'required|numeric|min:10',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'osa_code.required' => 'OSA code is required.',
            'osa_code.unique' => 'This OSA code already exists.',
            'bank_name.required' => 'Bank name is required.',
        ];
    }
}