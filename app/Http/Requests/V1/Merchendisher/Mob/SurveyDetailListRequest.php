<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SurveyDetailListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     * Merge route parameter 'header_id' into request input.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('header_id')) {
            $this->merge([
                'header_id' => $this->route('header_id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'header_id' => [
                'required',
                'integer',
                Rule::exists('survey_headers', 'id')->whereNull('deleted_at'),
            ],
            'per_page' => 'sometimes|integer|min:1|max:100', // optional pagination parameter
        ];
    }

    public function messages(): array
    {
        return [
            'header_id.required' => 'Header ID is required.',
            'header_id.integer'  => 'Header ID must be an integer.',
            'header_id.exists'   => 'The selected header ID is invalid or deleted.',
            'per_page.integer'   => 'Per page must be an integer.',
            'per_page.min'       => 'Per page must be at least 1.',
            'per_page.max'       => 'Per page cannot exceed 100.',
        ];
    }
}
