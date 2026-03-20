<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolePermissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

         return [
            'permissions' => 'required|array|min:1',
            'permissions.*.permission_id' => 'required|integer|exists:permissions,id',
            'permissions.*.menus' => 'nullable|array',
            'permissions.*.menus.*.menu_id' => 'nullable|integer|exists:menus,id',
            'permissions.*.menus.*.submenu_id' => 'nullable|integer|exists:sub_menu,id',
        ];
    }
}
