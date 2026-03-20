<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramPostRequest extends FormRequest
{  
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'planogram_id'     => 'required|integer',
            'merchendisher_id' => 'required|integer',
            'date'             => 'required|date',
            'customer_id'      => 'required|integer',
            'shelf_id'         => 'required|integer',
            'before_image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'after_image'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
