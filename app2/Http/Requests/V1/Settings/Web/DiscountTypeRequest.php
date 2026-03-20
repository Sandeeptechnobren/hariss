<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class DiscountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_code'   => 'nullable|string|max:50|unique:discount_types,discount_code',
            'discount_name'   => 'required|string|max:255',
            'discount_status' => 'required|integer|in:0,1',
        ];
    }
}
