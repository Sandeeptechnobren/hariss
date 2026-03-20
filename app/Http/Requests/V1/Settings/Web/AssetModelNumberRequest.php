<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class AssetModelNumberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code'       => 'required|string|max:50|unique:as_model_number,code',
            'name'       => 'required|string|max:150',
            'asset_type' => 'required|integer|exists:asset_types,id',
            'manu_type'  => 'required|integer|exists:am_manufacturer,id',
            'status'     => 'nullable|integer'
        ];
    }
}
