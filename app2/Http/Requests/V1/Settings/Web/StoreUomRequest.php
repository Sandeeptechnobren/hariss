<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'osa_code' => 'nullable|string|unique:uoms,osa_code,' . $this->route('uuid') . ',uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'UOM name is required.',
            'osa_code.nullable' => 'UOM code is required.',
            'osa_code.unique'   => 'This UOM code already exists.',
        ];
    }
}
