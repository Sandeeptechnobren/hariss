<?php
namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;

class ServiceVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

     public function rules(): array
    {
        return [

            'osa_code' => 'nullable|string|max:100',
            'ticket_type' => 'nullable|string|max:50',
            'time_in' => 'nullable|string|max:50',
            'time_out' => 'nullable|string|max:50',
            'ct_status' => 'nullable|string|max:200',
            'model_no' => 'nullable|string|max:50',
            'asset_no' => 'nullable|string|max:50',
            'serial_no' => 'nullable|string|max:50',
            'branding' => 'nullable|string|max:50',
            'scan_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',

            'outlet_code' => 'nullable|string|max:100',
            'outlet_name' => 'nullable|string|max:200',
            'owner_name' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'town_village' => 'nullable|string|max:100',
            'street'     => 'nullable|string|max:100',
            'district' => 'nullable|string|max:50',
            'contact_no' => 'nullable|string|max:50',
            'contact_no2' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:100',
            'longitude' => 'nullable|string|max:50',
            'latitude' => 'nullable|string|max:50',
            'technician_id' => 'nullable|integer',

            'is_machine_in_working' => 'nullable|in:0,1',
            'cleanliness' => 'nullable|in:0,1',
            'condensor_coil_cleand' => 'nullable|in:0,1',
            'gaskets' => 'nullable|in:0,1',
            'light_working' => 'nullable|in:0,1',
            'branding_no' => 'nullable|in:0,1',
            'propper_ventilation_available' => 'nullable|in:0,1',
            'leveling_positioning' => 'nullable|in:0,1',
            'stock_availability_in' => 'nullable|integer',

            // Images (2MB max)
            'is_machine_in_working_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'cleanliness_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'condensor_coil_cleand_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'gaskets_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'light_working_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'branding_no_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'propper_ventilation_available_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'leveling_positioning_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'stock_availability_in_img' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',

            'cooler_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'cooler_image2' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',

            'complaint_type' => 'nullable|string|max:2000',
            'comment' => 'nullable|string',
            'cts_comment' => 'nullable|string|max:500',
            'spare_part_used' => 'nullable|string|max:500',
            'pending_other_comment' => 'nullable|string|max:500',

            'any_dispute' => 'nullable|in:0,1',
            'current_voltage' => 'nullable|string|max:10',
            'amps' => 'nullable|string|max:10',
            'cabin_temperature' => 'nullable|integer',

            'work_status' => 'nullable|string|max:100',
            'wrok_status_pending_reson' => 'nullable|string',

            'spare_request' => 'nullable|string|max:500',
            'work_done_type' => 'nullable|string|max:100',
            'spare_details' => 'nullable|string|max:600',

            'type_details_photo1' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'type_details_photo2' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',

            'technical_behavior' => 'nullable|string|max:10',
            'service_quality' => 'nullable|string|max:10',
            'customer_signature' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',

            'nature_of_call_id' => 'nullable|integer',
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