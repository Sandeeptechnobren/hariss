<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class SubMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add Gate/Policy if needed
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'sometimes|string|max:20|unique:sub_menu,osa_code,' . $this->id,
            'name' => 'required|string|max:50',
            'menu_id' => 'required|exists:menus,id',
            'parent_id' => 'nullable|exists:sub_menu,id',
            'url' => 'nullable|string|max:255',
            'display_order' => 'integer',
            'action_type' => 'integer|in:0,1,2,3,4',
            'is_visible' => 'integer|in:0,1',
        ];
    }
}

