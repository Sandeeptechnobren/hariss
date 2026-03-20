<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'nullable|string|unique:tbl_tiers,osa_code',
            'name'     => 'nullable|string|max:255',
            'period'   => 'nullable|integer',
            'minpurchase' => 'nullable|integer',
            'maxpurchase' => 'nullable|integer',
        ];
    }
}