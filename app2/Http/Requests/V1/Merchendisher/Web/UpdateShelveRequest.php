<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShelveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

 public function rules(): array
{
    return [
        'shelf_name'       => 'nullable|string|max:255',
        'height'           => 'nullable|numeric|min:0',
        'width'            => 'nullable|numeric|min:0',
        'depth'            => 'nullable|numeric|min:0',
        'valid_from'       => 'nullable|date',
        'valid_to'         => 'nullable|date|after_or_equal:valid_from',
        'customer_ids'     => 'nullable|array',
        'customer_ids.*'   => 'integer',
        'merchendiser_ids' => 'nullable|array',
        'merchendiser_ids.*'=>'integer',
    ];
}
}