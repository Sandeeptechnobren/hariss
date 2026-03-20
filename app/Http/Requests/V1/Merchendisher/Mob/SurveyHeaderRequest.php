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
            'survey_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                $exists = Survey::where('id', $value)
                                ->whereNull('deleted_at')
                                ->exists();
                if (!$exists) {
                    $fail('The selected survey_id is invalid or has been deleted.');
                }
            }],
            'merchandiser_id' => 'required|integer',
            'date' => 'required|date',
            'answerer_name' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ];
    }
}
