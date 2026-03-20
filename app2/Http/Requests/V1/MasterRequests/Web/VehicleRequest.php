<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('id') ?? null;

        return [
            'vehicle_code' => [
                'nullable',
                'string',
                'max:100',
                'unique:tbl_vehicle,vehicle_code',
            ],

            'number_plat' => 'nullable|string|max:255',

            'vehicle_chesis_no' => [
                'nullable',
                'string',
                $vehicleId
                    ? "unique:tbl_vehicle,vehicle_chesis_no,{$vehicleId},id"
                    : 'unique:tbl_vehicle,vehicle_chesis_no',
            ],

            'capacity' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable',
            'vehicle_brand' => 'nullable|string',
            'owner_type' => [
                'required',
                'string',
                Rule::in(['company', 'agent']),
            ],
            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn ($input) => $input->owner_type === 'agent'),
                Rule::exists('tbl_warehouse', 'id')
                    ->where(fn ($query) => request('warehouse_id') != 0),
            ],
            'fuel_reading' => 'nullable|integer',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'opening_odometer' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_type.required' => 'The owner type is required.',
            'owner_type.in' => 'The owner type must be either "company" or "agent".',
            'warehouse_id.required_if' => 'The warehouse ID is required when owner type is agent.',
            'warehouse_id.exists' => 'The selected warehouse ID does not exist.',
        ];
    }
}
