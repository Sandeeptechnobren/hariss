<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class AssetTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'required|string|max:20|unique:asset_types,osa_code',
            'name'     => 'required|string|max:50',
            'status'   => 'nullable|integer',
        ];
    }
}
