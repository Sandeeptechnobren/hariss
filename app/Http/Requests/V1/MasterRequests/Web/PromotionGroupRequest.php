<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PromotionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [    
            'osa_code'  => 'nullable|string|unique:promotiongroups',
            'item'      => 'required|string',
            'name'      => 'required|string|max:50',
            'status'    => 'nullable|in:0,1',
        ];
    }
}