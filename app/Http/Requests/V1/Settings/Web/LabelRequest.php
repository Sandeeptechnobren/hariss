<?php
// app/Http/Requests/LabelRequest.php
namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust auth if needed
    }


    public function rules(): array
    {
        return [
            'osa_code' => 'sometimes|string|max:100|unique:labels,osa_code,' . $this->id,
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('labels')->ignore($this->id)->where(function ($query) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower($this->name)]);
                }),
            ],
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
