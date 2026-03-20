<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'osa_code'   => 'nullable|string|max:50|unique:drivers,osa_code',
            'driver_name'  => 'nullable|string|max:100',
            'contactno'   => 'nullable|integer',
            'vehicle_id'   => 'nullable|exists:tbl_vehicle,id',
            'device_id'   => 'nullable|exists:device_managements,id',
        ];
    }

    public function messages(): array
    {
        return [
            'driver_name.required'   => 'driver name is required.',
            'vehicle_id.required'   => 'vehicle is required.',
            'device_id.exists'     => 'Selected device does not exist.',
        ];
    }
}
