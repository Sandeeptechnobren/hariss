<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [    
            'code'    => 'nullable|string|unique:promotiongroups',
            'item'    => 'nullable|string',
            'name'    => 'nullable|string|max:50',
            'status'  => 'nullable|in:0,1',
        ];
    }
}