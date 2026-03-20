<?php

namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;

class AssetTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

     public function rules(): array
    {
    return [
        'osa_code'            => 'required|string|max:100',
        'route_id'            => 'nullable|integer',
        'salesman_id'         => 'nullable|integer',
        'customer_id'         => 'nullable|integer',
        'serial_no'           => 'nullable|string|max:150',
        'fridge_scan_tracking'=> 'nullable|boolean|in:0,1',
        'have_fridge'         => 'nullable|string',

        'image'               => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

        'latitude'            => 'nullable|numeric',
        'longitude'           => 'nullable|numeric',

        'outlet_name'         => 'nullable|string|max:255',
        'outlet_location'     => 'nullable|string',

        'outlet_contact'      => 'nullable|string|max:50',

        'outlet_photo'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

        'outlet_asm_id'       => 'nullable|integer',

        'last_visit_time'     => 'nullable|date',

        'inform_asm'          => 'nullable|boolean|in:0,1',

        'cooller_condition'   => 'nullable|string|max:100',

        'complaint_type'      => 'nullable|string|max:255',

        'comments'            => 'nullable|string',
    ];
    }
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
{
    throw new \Illuminate\Http\Exceptions\HttpResponseException(
        response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422)
    );
}
}
