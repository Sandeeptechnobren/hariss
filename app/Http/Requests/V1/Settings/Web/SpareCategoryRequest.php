<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class SpareCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'nullable|string|unique:spare_category,osa_code',
            'spare_category_name'   => 'required|string',
            'status' => 'required|integer|in:1,0',
        ]; 
    }
}