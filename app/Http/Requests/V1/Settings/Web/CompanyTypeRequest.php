<?php

namespace App\Http\Requests\V1\Settings\Web;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust if you use policies/permissions
    }

    public function rules(): array
    {
        $companyTypeId = $this->route('company_type') ?? $this->route('id');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('company_types', 'code')->ignore($companyTypeId),
            ],
            'name' => [
                'required',
                'string',
                'max:191',
            ],
            'status' => [
                'nullable',
                'in:0,1',
            ],
        ];
    }
}
