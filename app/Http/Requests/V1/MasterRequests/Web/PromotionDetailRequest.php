<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class PromotionDetailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
             'header_id' => [
                'required',
                Rule::exists('promotion_headers', 'id')->whereNull('deleted_at'),
            ],
            'lower_qty' => 'required|integer|min:0',
            'upper_qty' => 'required|integer|min:0',
            'free_qty' => 'required|integer|min:0',
            'uuid' => 'nullable|uuid|unique:promotion_details,uuid'
        ];
    }
}
