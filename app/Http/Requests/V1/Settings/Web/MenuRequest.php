<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All authenticated users can create/update menus
    }

    public function rules(): array
    {
        $uuid = $this->route('uuid'); // For update requests

        return [
            'name' => 'required|string|max:55|unique:menus,name,' . $uuid . ',uuid',
            'icon' => 'nullable|string|max:255', // Path to image
            'url' => 'nullable|string|max:255', // Valid URL
            'display_order' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|integer|in:0,1',
            'status' => 'required|integer|in:0,1',
            'osa_code' => 'sometimes|string|max:50|unique:menus,osa_code,' . $uuid . ',uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Menu name is required.',
            'name.unique' => 'This menu name already exists.',
            'icon.max' => 'Icon length cannot exceed 255 characters.',
            'url.max' => 'URL length cannot exceed 255 characters.',
            'display_order.integer' => 'Display order must be a number.',
            'display_order.min' => 'Display order cannot be negative.',
            'is_visible.in' => 'Is visible must be 0 (hidden) or 1 (visible).',
            'status.in' => 'Status must be 0 (Inactive) or 1 (Active).',
            'osa_code.unique' => 'This OSA code already exists.',
        ];
    }
}
