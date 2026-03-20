<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // 🔹 Basic details
            'osa_code'   => 'nullable|string|max:50',
            'ticket_type'   => 'nullable|string|max:50',
            'technician_id' => 'nullable|integer',
            'ticket_status'   => 'nullable|integer',
            'work_status'   => 'nullable|integer',

            // 🔹 Time & asset info
            'time_in'   => 'nullable|date',
            'time_out'  => 'nullable|date',
            'model_no'  => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'asset_no'  => 'nullable|string|max:100',
            'branding'  => 'nullable|string|max:100',
            'chiller_id'  => 'nullable|integer',

            // 🔹 Contacts
            'outlet_code'    => 'nullable|string|max:150',
            'outlet_name'    => 'nullable|string|max:150',
            'owner_name'    => 'nullable|string|max:150',
            'landmark'    => 'nullable|string|max:150',
            'location'    => 'nullable|string|max:150',
            'town_village'    => 'nullable|string|max:150',
            'district'    => 'nullable|string|max:150',
            'contact_no'    => 'nullable|string|max:150',
            'contact_no2'    => 'nullable|string|max:150',
            'contact_person' => 'nullable|string|max:150',

            // 🔹 Technical readings
            'complaint_type'    => 'nullable|numeric',
            'current_voltage'    => 'nullable|numeric',
            'amps'               => 'nullable|numeric',
            'cabin_temperature'  => 'nullable|numeric',

            // 🔹 Status & comments
            'ct_status'           => 'nullable|integer',
            'cts_comment'         => 'nullable|string',
            'technical_behavior' => 'nullable|string',
            'service_quality'     => 'nullable|string',

            // 🔹 Machine checks (boolean)
            'is_machine_in_working'         => 'nullable|boolean',
            'cleanliness'                   => 'nullable|boolean',
            'condensor_coil_cleand'         => 'nullable|boolean',
            'gaskets'                       => 'nullable|boolean',
            'light_working'                 => 'nullable|boolean',
            'branding_no'                   => 'nullable|boolean',
            'propper_ventilation_available' => 'nullable|boolean',
            'leveling_positioning'          => 'nullable|boolean',
            'stock_availability_in'         => 'nullable|boolean',

            // 🔹 Files
            'scan_image'                        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'is_machine_in_working_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cleanliness_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'condensor_coil_cleand_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'gaskets_img'                       => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'light_working_img'                 => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'branding_no_img'                   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'propper_ventilation_available_img' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'leveling_positioning_img'          => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'stock_availability_in_img'         => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cooler_image'                      => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'cooler_image2'                     => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'type_details_photo1'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'type_details_photo2'               => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'customer_signature'                => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',

            // 🔹 Other
            'nature_of_call_id' => 'nullable|string|max:200',
        ];
    }
}
