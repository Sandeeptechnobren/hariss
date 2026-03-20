<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, 
     */

public function rules()
{
    return [
        'name'       => ['nullable', 'string', 'max:55'],
        'code'       => ['nullable', 'string', 'max:20'],
        'valid_from' => ['nullable', 'date'],
        'valid_to'   => ['nullable', 'date', 'after_or_equal:valid_from'],
        'merchendisher_id'   => ['required', 'array'],
        'merchendisher_id.*' => ['integer', 'exists:salesman,id'],

        'customer_id'        => ['required', 'array'],
        'customer_id.*'      => ['integer', 'exists:tbl_company_customer,id'],
        'images'   => ['nullable', 'array'],
        'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
    ];
}

}
