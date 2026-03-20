<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreShelveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
   return [
    'code'           => 'nullable|string|max:20|unique:shelves,code',
    'shelf_name'     => 'required|string|max:50',
    'height'         => 'required|numeric|min:0',
    'width'          => 'required|numeric|min:0',
    'depth'          => 'required|numeric|min:0',
    'valid_from'     => 'required|date',
    'valid_to'       => 'required|date|after_or_equal:valid_from',
    'merchendiser_ids'  => 'required|array',
    'merchendiser_ids.*'=> 'integer',
    'customer_id'    => 'nullable|integer',
    'customer_ids.*' => 'integer',
];
    }

    public function messages()
    {
        return [
            'customer_ids.required' => 'Please select at least one customer.',
            'customer_ids.*.exists' => 'One or more selected customers are invalid.',
        ];
    }
    }
