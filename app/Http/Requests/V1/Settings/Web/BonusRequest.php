<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class BonusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code' => 'nullable|string|unique:tbl_bonus,osa_code',
            'item_id'  => 'required|integer|exists:items,id',
            'volume'   => 'required|integer',
            'bonus_points' => 'required|integer',
            'reward_basis' => 'required|integer|in:1,2',
        ]; 
    }
}