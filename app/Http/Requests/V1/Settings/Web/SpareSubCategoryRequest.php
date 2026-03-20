<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class SpareSubCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'nullable|string|unique:spare_subcategory,osa_code',
            'spare_subcategory_name'   => 'required|string',
            'spare_category_id' => 'required|integer|exists:spare_category,id',
            'status' => 'required|integer|in:1,0',
        ]; 
    }
}