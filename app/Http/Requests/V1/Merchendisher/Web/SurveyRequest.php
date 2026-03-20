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
            'status' => 'required|in:active,inactive',
            'survey_type' => 'nullable|integer|max:50',
            'merchandisher_id'   => 'nullable|array',
            'merchandisher_id.*' => 'integer|exists:salesman,id',
            'customer_id'        => 'nullable|array',
            'customer_id.*'      => 'integer|exists:tbl_company_customer,id',
            'asset_id'           => 'nullable|array',
            'asset_id.*'         => 'integer|exists:tbl_add_chillers,id',
        ];
    }
}