<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class AssetManufacturerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code'  => 'required|string|max:50|unique:am_manufacturer,osa_code',
            'name'  => 'required|string|max:150',
            'asset_type'  => 'required|integer|exists:asset_types,id',
            'status'=> 'nullable|integer',
        ];
    }
}
