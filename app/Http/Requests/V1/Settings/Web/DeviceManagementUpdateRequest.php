<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class DeviceManagementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code'   => 'nullable|string|max:50|unique:device_managements,osa_code',
            'manufacturing_id'   => 'nullable|integer',
            'device_name'  => 'nullable|string|max:100',
            'modelno'      => 'nullable|string',
            'IMEI_1'   => 'nullable|string',
            'IMEI_2'   => 'nullable|string',
        ];
    }
}
