<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize() {
        return true;
    }

    public function rules() {
        $permissionId = $this->route('id');
        return [
            'name' => 'sometimes|string|unique:permissions,name,' . $permissionId
        ];
    }
}
