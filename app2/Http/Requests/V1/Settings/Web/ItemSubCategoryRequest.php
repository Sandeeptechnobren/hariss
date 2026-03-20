<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class ItemSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_category_code'=>'sometimes',
            'category_id' => 'required|integer|exists:item_categories,id',
            'sub_category_name' => 'required|string|max:255',
            'status' => 'nullable|in:0,1'
        ];
    }
}
