<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {   return [
            'osa_code' => 'nullable|string|unique:tbl_bonus,osa_code',
            'item_id'  => 'nullable|integer|exists:items,id',
            'volume'   => 'nullable|integer',
            'bonus_points' => 'nullable|integer',
            'reward_basis' => 'nullable|integer|in:1,2',
        ]; 
    }
}