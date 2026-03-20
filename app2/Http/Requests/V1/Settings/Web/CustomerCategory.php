<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerCategory extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_channel_id' => 'required|exists:outlet_channel,id',
            'customer_category_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('customer_categories', 'customer_category_code')
                    ->ignore($this->route('id')),
            ],
            'customer_category_name' => 'required|string|max:255',
            'status' => 'nullable|in:0,1'
        ];
    }
}
