<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $brandId = $this->route('brand') ? $this->route('brand')->id : null;

        return [
            'status'=> 'nullable|in:0,1',
            'name' => 'required|string|max:255',
            'osa_code' => 'nullable|string|max:50',
        ];
    }
}