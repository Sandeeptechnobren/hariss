<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApprovalWorkflowRequest extends FormRequest
{
    public function rules()
    {
        return [
            'workflow_name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'is_active' => 'boolean',
            'steps' => 'array',
            'steps.*.id' => 'sometimes|integer|exists:approval_steps,id',
            'steps.*.step_order' => 'required_with:steps|integer|min:1',
            'steps.*.role_id' => 'nullable|integer',
            'steps.*.user_id' => 'nullable|integer',
            'steps.*.approval_type' => 'required_with:steps|string|in:sequential,parallel',
            'steps.*.auto_approve' => 'required_with:steps|boolean',
            'steps.*.condition' => 'nullable|string'
        ];
    }
}
