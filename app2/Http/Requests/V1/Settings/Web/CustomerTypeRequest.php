<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class CustomerTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // you can add policy check here
    }

public function rules()
{
    return [
        'code' => 'sometimes|string|max:50|unique:customer_types,code',
        'name' => 'required|string|max:255',
        'status' => 'required|in:0,1',
    ];
}

}
