<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'lables' => 'nullable|array',
            'labels.*' => 'integer|exists:labels,id',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
