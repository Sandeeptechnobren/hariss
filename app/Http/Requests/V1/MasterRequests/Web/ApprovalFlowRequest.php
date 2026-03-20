<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

        public function rules()
    {
        return [
            'menu_id' => 'required|integer|exists:menus,id',
            'submenu_id' => 'nullable|integer|exists:sub_menu,id',
            'workflow_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'array|min:1',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.role_id' => 'nullable|integer',
            'steps.*.user_id' => 'nullable|integer',
            'steps.*.approval_type' => 'required|string|in:sequential,parallel',
            'steps.*.auto_approve' => 'required|boolean',
            'steps.*.condition' => 'nullable|string'
        ];
    }
       
}
