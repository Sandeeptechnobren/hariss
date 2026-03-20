<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesmanAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'time_out'      => 'nullable|date_format:Y-m-d H:i:s',
            'latitude_out'  => 'nullable|numeric',
            'longitude_out' => 'nullable|numeric',
            'out_img'       => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'check_out'     => 'nullable|in:true,false,1,0',
        ];
    }
}
