<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class DeviceManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code'   => 'nullable|string|max:50|unique:device_managements,osa_code',
            'manufacturing_id'   => 'required|integer',
            'device_name'  => 'required|string|max:100',
            'modelno'      => 'required|string',
            'IMEI_1'   => 'required|string',
            'IMEI_2'   => 'required|string',
        ];
    }
}
