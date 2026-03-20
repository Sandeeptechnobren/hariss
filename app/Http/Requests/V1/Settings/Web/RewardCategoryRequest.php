<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class RewardCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
       return [
            'osa_code' => 'nullable|string|unique:tbl_tiers,osa_code',
            'name'     => 'required|string|max:255',
            'image'    => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'points_required' => 'required|integer',
            'stock_qty' => 'required|numeric',
            'type'      => 'required|string'
        ];
    }
}