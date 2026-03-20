<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class RewardUpdateRequest extends FormRequest
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
            'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'points_required' => 'nullable|integer',
            'stock_qty' => 'nullable|numeric',
            'type'      => 'nullable|string'
        ];
    }
}