<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_code' => 'required|string|max:150',
            'country_name' => 'required|string|max:150',
            'currency'     => 'nullable|string|max:5',
            'status'       => 'nullable|integer|in:0,1',
        ];
    }
}
