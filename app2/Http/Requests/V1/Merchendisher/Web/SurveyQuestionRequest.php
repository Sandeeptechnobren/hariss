<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
         'survey_id' => [
            'required',
            Rule::exists('surveys', 'id')->whereNull('deleted_at'),
        ],
            'question' => 'required|string',
            'question_type' => 'required|in:comment box,check box,radio button,textbox,selectbox',
            'question_based_selected' => 'nullable',
            'survey_question_code' => 'nullable|string|unique:survey_questions,survey_question_code',
        ];
    }
}