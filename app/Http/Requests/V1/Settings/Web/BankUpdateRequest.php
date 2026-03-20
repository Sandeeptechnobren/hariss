<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class BankUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $bankId = $this->route('uuid');

        return [
            'osa_code'       => 'nullable|string|max:50|unique:tbl_banks,osa_code,' . $bankId . ',uuid',
              'bank_name' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'account_number' => 'nullable|integer',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}