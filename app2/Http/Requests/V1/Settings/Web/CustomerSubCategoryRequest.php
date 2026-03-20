<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'customer_category_id' => 'required|exists:customer_categories,id',
            'customer_sub_category_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('customer_sub_categories', 'customer_sub_category_code')
                    ->ignore($this->route('id')),
            ],
            'customer_sub_category_name' => 'required|string|max:255',
            'status' => 'nullable|in:0,1',
        ];
    }
}
