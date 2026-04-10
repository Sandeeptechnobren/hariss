<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Models\Survey;

class SurveyHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

  public function rules(): array
{
    return [
        'merchandiser_id' => ['required', 'integer'],
        'date'            => ['required', 'date'],
        'answerer_name'   => ['nullable', 'string', 'max:50'],
        'address'         => ['nullable', 'string'],
        'phone'           => ['nullable', 'string', 'max:20'],
        'survey_id'       => ['required', 'integer', 'exists:surveys,id'],
        'details'                 => ['required', 'array', 'min:1'],
        'details.*.question_id'   => ['required', 'integer', 'exists:survey_questions,id'],
        'details.*.answer'        => ['nullable', 'string', 'max:2000'],
    ];
}

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        throw new ValidationException($validator, response()->json([
            'message' => 'Validation Failed',
            'errors' => $errors
        ], 422));
    }
}
