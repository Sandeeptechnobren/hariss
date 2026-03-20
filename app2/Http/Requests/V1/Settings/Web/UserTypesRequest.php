<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
 public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                Rule::unique('user_types', 'code')->ignore($id),
            ],
            'name' => 'required|string|max:255',
            'status' => 'required|integer|in:0,1',
        ];
    }
}
