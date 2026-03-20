<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
         return [
            'osa_code' => 'nullable|string|unique:tbl_spare,osa_code',
            'spare_name'   => 'required|string',
            'spare_categoryid' => 'required|integer|exists:spare_category,id',
            'spare_subcategoryid' => 'required|integer|exists:spare_subcategory,id',
            'plant' => 'required|string',
        ]; 
    }
}