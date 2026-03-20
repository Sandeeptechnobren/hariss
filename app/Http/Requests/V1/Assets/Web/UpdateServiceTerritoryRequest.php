<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceTerritoryRequest extends FormRequest
{
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
            'osa_code' => 'sometimes|string|unique:tbl_service_territory,osa_code,' .
                $this->route('uuid') . ',uuid',

            'warehouse_id'   => 'sometimes|array|min:1',
            'warehouse_id.*' => 'integer',

            'region_id'      => 'sometimes|array|min:1',
            'region_id.*'    => 'integer',

            'area_id'        => 'sometimes|array|min:1',
            'area_id.*'      => 'integer',

            'technician_id'  => 'sometimes|integer|exists:salesman,id',
        ];
    }
}
