<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpareSubCRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'nullable|string|unique:spare_subcategory,osa_code',
            'spare_subcategory_name'   => 'nullable|string',
            'spare_category_id' => 'nullable|integer|exists:spare_category,id',
            'status' => 'nullable|integer|in:1,0',
        ]; 
    }
}