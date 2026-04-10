<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountSettingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'discount_amt' => 'required|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
        ];
    }
}
