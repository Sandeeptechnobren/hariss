<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class AssetBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code'      => 'nullable|string|max:20',
            'name'          => 'required|string|max:50',
            'status'        => 'nullable|integer',
        ];
    }
}
