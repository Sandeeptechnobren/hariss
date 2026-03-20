<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $roleId = $this->route('role');
        return [
            'name' => 'sometimes|string|unique:roles,name,' . $roleId,
            'permissions' => 'sometimes|array',
            'labels' => 'nullable|array',
            'labels.*' => 'integer|exists:labels,id',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
