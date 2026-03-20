<?php

namespace App\Http\Requests\V1\Settings\Web;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PromotionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
        'code' => [
            'sometimes',
            'string',
            'max:50',
        ],
        'name' => 'required|string|max:255',
        'status' => 'required|in:0,1',
        'created_user' => 'sometimes|integer|exists:users,id',
        'updated_user' => 'sometimes|integer|exists:users,id',
    ];
    }
}
