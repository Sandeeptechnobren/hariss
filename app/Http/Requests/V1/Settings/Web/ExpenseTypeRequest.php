<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_type_code'=>'sometimes|string',
            'expense_type_name'   => 'required|string|max:255',
            'expense_type_status' => 'required|integer|in:0,1',
        ];
    }
}
