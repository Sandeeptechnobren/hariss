<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class InstallationOrderHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'sometimes|string|max:20|unique:installation_order_headers,osa_code,' . $this->id,
            'name' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
