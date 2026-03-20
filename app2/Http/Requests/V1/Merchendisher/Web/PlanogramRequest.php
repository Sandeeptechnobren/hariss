<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramRequest extends FormRequest
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
<<<<<<< HEAD
    public function rules()
    {
       return [
                'name'        => ['required', 'string', 'max:55'],
                'code'        => ['nullable', 'string', 'max:20'],
                'valid_from'  => ['required', 'date'],
                'valid_to'    => ['required', 'date', 'after_or_equal:valid_from'],
                'merchendisher_id'     => ['required', 'array'],
                'merchendisher_id.*'   => ['integer', 'exists:salesman,id'],
                'customer_id'         => ['required', 'array'],
                'customer_id.*'       => ['integer', 'exists:tbl_company_customer,id'],
                'shelf_id'            => ['required', 'array'],
                'shelf_id.*'          => ['integer', 'exists:shelves,id'], 
                'images'                           => ['nullable', 'array'],
                'images.*.*.*.shelf_id'            => ['required', 'integer', 'exists:shelves,id'],
                'images.*.*.*.image'               => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
            ];
    }
=======
  public function rules()
{
  return [
    'name'        => ['required', 'string', 'max:55'],
    'code'        => ['nullable', 'string', 'max:20'],
    'valid_from'  => ['required', 'date'],
    'valid_to'    => ['required', 'date', 'after_or_equal:valid_from'],
    'merchendisher_id'     => ['required', 'array'],
    'merchendisher_id.*'   => ['integer', 'exists:salesman,id'],
    'customer_id'         => ['required', 'array'],
    'customer_id.*'       => ['integer', 'exists:tbl_company_customer,id'],
    'shelf_id'            => ['required', 'array'],
    'shelf_id.*'          => ['integer', 'exists:shelves,id'], 
    'images'                           => ['nullable', 'array'],
    'images.*.*.*.shelf_id'            => ['required', 'integer', 'exists:shelves,id'],
    'images.*.*.*.image'               => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif', 'max:2048'],
];
}
>>>>>>> ae60f28181c47afb1219a37ca58155c941012dd6
}
