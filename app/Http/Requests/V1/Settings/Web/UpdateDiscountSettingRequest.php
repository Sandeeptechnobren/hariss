<?php

namespace App\Http\Requests\V1\Settings\Web;

class UpdateDiscountSettingRequest extends StoreDiscountSettingRequest
{

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'discount_amt' => 'sometimes|numeric|min:0',
            'qty' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:0,1',
        ];
    }
}
