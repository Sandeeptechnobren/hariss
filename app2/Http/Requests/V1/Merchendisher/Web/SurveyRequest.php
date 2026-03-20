<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class SurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'survey_name' => 'required|string|max:100',
            'survey_code' => 'nullable|unique:surveys,survey_code,' . ($this->survey ? $this->survey->id : 'NULL') . ',id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,',
        ];
    }
}