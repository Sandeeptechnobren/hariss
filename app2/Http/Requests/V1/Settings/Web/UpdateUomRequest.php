<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid'); // route parameter from URL

        return [
            'name' => 'sometimes|string|max:255',
            'osa_code' => 'sometimes|string|max:100|unique:uom,osa_code,' . $uuid . ',uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'name.sometimes' => 'UOM name is required.',
            'osa_code.sometimes' => 'UOM code is required.',
        ];
    }
}
