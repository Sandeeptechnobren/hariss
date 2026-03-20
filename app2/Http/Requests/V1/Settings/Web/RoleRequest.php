<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');

        return [
            'role_name'     => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role_name')->ignore($uuid, 'uuid'),
            ],
            'role_activity' => ['nullable', 'integer', 'min:0', 'max:10'],
            'menu_id'       => ['required', 'string', 'max:255'],
            'agent_id'      => ['nullable', 'integer'],
            'warehouse_id'  => ['nullable', 'integer'],
        ];
    }
}
