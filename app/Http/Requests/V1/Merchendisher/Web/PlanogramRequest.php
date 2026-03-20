<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name'       => ['required', 'string', 'max:55'],
            'valid_from' => ['required', 'date'],
            'valid_to'   => ['required', 'date', 'after_or_equal:valid_from'],

            // Can be single or multiple IDs
            'merchendisher_id'   => ['required', 'array'],
            'merchendisher_id.*' => ['integer', 'exists:salesman,id'],

            'customer_id'        => ['required', 'array'],
            'customer_id.*'      => ['integer', 'exists:tbl_company_customer,id'],

            // Images can be single or multiple
            'images'             => ['nullable', 'array'],
            'images.*'           => ['image', 'mimes:jpg,jpeg,png,jpg', 'max:2048'],
        ];
    }
}
