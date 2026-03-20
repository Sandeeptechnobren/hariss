<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class AreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // set your auth logic if needed
    }

    public function rules(): array
    {
        return [
            'area_code'   => 'required|string|max:200|unique:tbl_areas,area_code',
            'area_name'   => 'required|string|max:200',
            'region_id'   => 'required|exists:tbl_region,id',
            'status'      => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'area_name.required'   => 'Area name is required.',
            'region_id.required'   => 'Region is required.',
            'region_id.exists'     => 'Selected region does not exist.',
        ];
    }
}
