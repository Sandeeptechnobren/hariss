<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'warehouse_id' => $this->warehouse_id === "" ? null : $this->warehouse_id,
            'vehicle_id'   => $this->vehicle_id === "" ? null : $this->vehicle_id,
            'route_type'   => isset($this->route_type) ? $this->route_type : null,
            'route_type'   => isset($this->route_type) ? $this->route_type : null,
            'status'       => isset($this->status) ? (int) $this->status : null,
        ]);
    }

    public function rules(): array
    {
        // Get current route ID from route model binding
        $routeId = $this->route('route')?->id ?? null;
        // dd($routeId);

        return [
            // route_code required on create, optional on update
            'route_code'   => 'nullable|string',
            'route_name'   => 'required|string|max:50',
            'description'  => 'nullable|string',
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'route_type'   => 'required|integer', 
            'route_type.*' => 'required|string|exists:route_types,id',
            'vehicle_id'   => 'nullable|integer',
            'status'       => 'nullable|integer|in:0,1',
        ];
    }
}