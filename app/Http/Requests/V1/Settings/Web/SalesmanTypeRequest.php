<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salesman_type_code' => 'required|string|max:50|unique:salesman_types,salesman_type_code',
            'salesman_type_name'   => 'required|string|max:100',
            'salesman_type_status' => 'required|integer|in:0,1',
        ];
    }
}
