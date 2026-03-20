<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanAttendanceRequest extends FormRequest
{
     public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'salesman_id'   => 'required|integer',
            'route_id'      => 'nullable|integer',
            'warehouse_id'  => 'nullable|integer',
            'attendance_date' => 'required|date',
            'time_in'       => 'nullable|date_format:Y-m-d H:i:s',
            'latitude_in'   => 'nullable|numeric',
            'longitude_in'  => 'nullable|numeric',
            'in_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            // 'time_out'      => 'nullable|date_format:Y-m-d H:i:s',
            // 'latitude_out'  => 'nullable|numeric',
            // 'longitude_out' => 'nullable|numeric',
            // 'out_img'       => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'check_in'      => 'nullable|in:true,false,1,0',
            // 'check_out'     => 'nullable|boolean',
        ];
    }
public function messages(): array
    {
        return [
            'salesman_id.required' => 'Salesman is required',
            'salesman_id.integer'  => 'Salesman must be a valid ID',
            'attendance_date.required' => 'Attendance date is required',
            'attendance_date.date' => 'Attendance date must be valid date',
            'time_in.date_format' => 'Time in must be in Y-m-d H:i:s format',
            'latitude_in.numeric' => 'Latitude must be numeric',
            'longitude_in.numeric' => 'Longitude must be numeric',
            'in_img.mimes' => 'Image must be jpg, jpeg or png',
            'in_img.max' => 'Image size must be less than 2MB',
            'check_in.in' => 'Check in must be true/false or 1/0',
        ];
    }
protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}