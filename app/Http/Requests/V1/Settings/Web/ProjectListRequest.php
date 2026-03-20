<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class ProjectListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'salesman_type_id' => 'nullable|integer|exists:salesman_type,id',
            'status' => 'required|integer|in:0,1',
            'created_user' => 'nullable|string|max:100',
            'updated_user' => 'nullable|string|max:100',
            'deleted_user' => 'nullable|string|max:100',
        ];
    }
}
