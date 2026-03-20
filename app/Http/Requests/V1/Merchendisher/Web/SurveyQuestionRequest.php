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

        // 🔥 multiple questions array
        'questions' => 'required|array|min:1',

        // 🔥 each question validation
        'questions.*.question' => 'required|string',

        'questions.*.question_type' => [
            'required',
            Rule::in([
                'comment box',
                'check box',
                'radio button',
                'textbox',
                'selectbox',
            ]),
        ],

        'questions.*.question_based_selected' => 'nullable',

        'questions.*.survey_question_code' => [
            'nullable',
            'string',
            Rule::unique('survey_questions', 'survey_question_code'),
        ],
    ];
}
}