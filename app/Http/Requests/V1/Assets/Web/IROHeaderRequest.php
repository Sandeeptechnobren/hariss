<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class IROHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'osa_code'       => 'sometimes|string|max:20|unique:tbl_IRO_headers,osa_code,' . $this->id,
            'uuid'           => 'sometimes|uuid',

            'crf_id'   => ['required', 'array'],
            'crf_id.*' => ['integer', 'exists:chiller_requests,id'],
        ];
    }
}
