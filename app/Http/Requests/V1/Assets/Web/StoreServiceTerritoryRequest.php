<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceTerritoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['warehouse_id', 'region_id', 'area_id'] as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => array_filter(
                        array_map('trim', explode(',', $this->$field))
                    )
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'nullable|string|unique:tbl_service_territory,osa_code',

            // ✅ Only validate as array + integers
            // ❌ DO NOT use exists here
            'warehouse_id'   => 'required|array|min:1',
            'warehouse_id.*' => 'integer',

            'region_id'      => 'required|array|min:1',
            'region_id.*'    => 'integer',

            'area_id'        => 'required|array|min:1',
            'area_id.*'      => 'integer',

            'technician_id'  => 'required|integer|exists:salesman,id',
        ];
    }
}
