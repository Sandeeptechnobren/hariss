<?php

namespace App\Http\Requests\V1\MasterRequests\Web;
use Illuminate\Foundation\Http\FormRequest;

class RegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_code' => 'nullable|string|max:20|unique:tbl_region,region_code,' . ($this->region->id ?? 'NULL') . ',id,deleted_at,NULL',
            'region_name' => 'required|string|max:200',
            'company_id'  => 'required|exists:tbl_company,id',
            'status'      => 'nullable|integer|in:0,1',
        ];
    }
}
