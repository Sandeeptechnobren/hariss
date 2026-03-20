<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class TierRequest extends FormRequest
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
            'period'   => 'required|integer',
            'minpurchase' => 'required|integer',
            'maxpurchase' => 'required|integer',
            'period_category' => 'nullable|integer|in:1,2,3',
            'expiray_period'  => 'nullable|string',
        ]; 
    }
}