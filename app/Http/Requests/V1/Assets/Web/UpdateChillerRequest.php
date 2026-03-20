<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AddChiller;

class UpdateChillerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // protected function prepareForValidation()
    // {
    //     // Convert vender_details to array (if coming comma-separated)
    //     if ($this->filled('vender_details')) {
    //         $details = is_array($this->vender_details)
    //             ? $this->vender_details
    //             : explode(',', $this->vender_details);

    //         $this->merge([
    //             'vender_details' => array_map('trim', $details)
    //         ]);
    //     }
    // }

    public function rules(): array
    {
        $id = null;

        // For update → fetch ID using UUID
        if ($this->route('uuid')) {
            $chiller = AddChiller::where('uuid', $this->route('uuid'))->first();
            $id = $chiller ? $chiller->id : null;
        }

        return [
            'osa_code' => [
                'sometimes',
                'string',
                'max:200',
                Rule::unique('tbl_add_chillers', 'osa_code')->ignore($id)
            ],

            'serial_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('tbl_add_chillers', 'serial_number')->ignore($id)
            ],

            // Asset Category (FK)
            'assets_category' => 'sometimes|integer|exists:asset_types,id',

            // Model Number (FK)
            'model_number' => 'sometimes|integer|exists:as_model_number,id',

            'acquisition' => 'sometimes|string|max:50',

            // Vendor (single)
            'vender' => 'sometimes|integer|exists:tbl_vendor,id',

            // Manufacturer (FK)
            'manufacturer' => 'sometimes|integer|exists:am_manufacturer,id',

            'country_id' => 'sometimes|integer|exists:tbl_country,id',

            'assets_type' => 'sometimes|string|max:100',

            'sap_code' => 'sometimes|string|max:50',

            'status' => 'sometimes|integer',

            'remarks' => 'sometimes|string|max:50',

            'branding' => 'sometimes|integer|exists:assets_branding,id',

            'trading_partner_number' => 'sometimes|string|max:50',

            'capacity' => 'sometimes|integer',

            'manufacturing_year' => 'sometimes|string|max:50',
        ];
    }

   
}
