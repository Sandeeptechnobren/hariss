<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class ServiceTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'nullable|string|unique:services_type,code,' . $this->route('uuid') . ',uuid',
            'name' => 'required|string|max:191',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
