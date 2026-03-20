<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class SurveyDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {

        return [
            'header_id' => 'required|exists:survey_headers,id',
            'question_id' => 'required|exists:survey_questions,id',
            'answer' => 'required|string|max:2000',
        ];

return [
    'header_id' => [
        'required',
        Rule::exists('survey_headers', 'id')->whereNull('deleted_at')
    ],
    'question_id' => [
        'required',
        Rule::exists('survey_questions', 'id')->whereNull('deleted_at')
    ],
            'answer' => 'required|string|max:2000',
];
    }
}