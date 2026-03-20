<?php

namespace App\Http\Requests\V1;
use Illuminate\Foundation\Http\FormRequest;

class CodeReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); 
    }
    public function rules(): array
    {
        return [
            'model_name' => [
                'required', 
                'string', 
                'max:50',
                function ($attribute, $value, $fail) {
                    if (!config('codes.' . $value)) {
                        $fail("The selected {$attribute} is invalid or not configured for code generation.");
                    }
                },
            ],
            'payload' => ['sometimes', 'array'],
            'reserved_code' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
