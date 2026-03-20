<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManufacturingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status'=> 'nullable|in:0,1',
            'name' => 'nullable|string|max:55',
            'osa_code' => 'nullable|string|max:50',
        ];
    }
}