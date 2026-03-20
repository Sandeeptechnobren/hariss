<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddChillerRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return true;
    // }

    // protected function prepareForValidation()
    // {
    //     if ($this->filled('vender_details')) {
    //         $details = is_array($this->vender_details)
    //             ? $this->vender_details
    //             : explode(',', $this->vender_details);

    //         $this->merge([
    //             'vender_details' => array_map('trim', $details)
    //         ]);
    //     }
    // }

    // public function rules()
    // {
    //     $id = null;

    //     if ($this->route('uuid')) {
    //         $chiller = \App\Models\AddChiller::where('uuid', $this->route('uuid'))->first();
    //         $id = $chiller ? $chiller->id : null;
    //     }

    //     return [
    //         'fridge_code' => [
    //             'sometimes',
    //             'string',
    //             'max:200',
    //             Rule::unique('add_chiller', 'fridge_code')->ignore($id)
    //         ],
    //         'serial_number' => [
    //             'sometimes',
    //             'string',
    //             'max:50',
    //             Rule::unique('add_chiller', 'serial_number')->ignore($id)
    //         ],
    //         'asset_number' => [
    //             'sometimes',
    //             'string',
    //             'max:50',
    //             Rule::unique('add_chiller', 'asset_number')->ignore($id)
    //         ],
    //         'model_number' => 'sometimes|string|max:50',
    //         'description'  => 'nullable|string',
    //         'acquisition'  => 'nullable|string|max:50',
    //         'vender_details' => 'nullable|array',
    //         'vender_details.*' => 'integer|exists:tbl_vendor,id',
    //         'manufacturer' => 'nullable|string|max:200',
    //         'country_id'   => 'sometimes|integer|exists:tbl_country,id',
    //         'type_name'    => 'nullable|string|max:100',
    //         'sap_code'     => 'nullable|string|max:50',
    //         'status'       => 'integer|in:0,1',
    //         'is_assign'    => 'integer|in:0,1,2',
    //         'customer_id'  => 'sometimes|integer',
    //         'agreement_id' => 'nullable|integer',
    //         'document_type' => 'nullable|in:ACF,CRF',
    //         'document_id'  => 'nullable|integer',
    //     ];
    // }

    // public function validated($key = null, $default = null)
    // {
    //     $data = parent::validated();

    //     if (isset($data['vender_details']) && is_array($data['vender_details'])) {
    //         $data['vender_details'] = implode(',', $data['vender_details']);
    //     }

    //     return $data;
    // }

    public function authorize(): bool
    {
        return true;
    }

    // protected function prepareForValidation()
    // {
    //     // vendor_details ko array me convert kar lo
    //     if ($this->filled('vender_details')) {
    //         $details = is_array($this->vender_details)
    //             ? $this->vender_details
    //             : explode(',', $this->vender_details);

    //         $this->merge([
    //             'vender_details' => array_map('trim', $details)
    //         ]);
    //     }
    // }

    public function rules()
    {
        $id = null;

        // Update ke case me id fetch karo
        if ($this->route('uuid')) {
            $chiller = \App\Models\AddChiller::where('uuid', $this->route('uuid'))->first();
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
            // 'asset_number' => [
            //     'sometimes',
            //     'string',
            //     'max:50',
            //     Rule::unique('add_tbl_add_chillerschiller', 'asset_number')->ignore($id)
            // ],
            'assets_category' => 'required|integer|exists:asset_types,id',
            'model_number' => 'required|integer|exists:as_model_number,id',
            'acquisition'  => 'nullable|string|max:50',
            'vender'       => 'integer|exists:tbl_vendor,id',
            'manufacturer' => 'required|integer|exists:am_manufacturer,id',
            'country_id'   => 'sometimes|integer|exists:tbl_country,id',
            'assets_type'    => 'nullable|string|max:100',
            'sap_code'     => 'nullable|string|max:50',
            'status'       => 'integer',
            'remarks'    => 'nullable|string|max:50',
            'branding'  => 'sometimes|integer|exists:assets_branding,id',
            'trading_partner_number' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer',
            'manufacturing_year'  => 'nullable|string|max:50',
        ];
    }

    // public function validated($key = null, $default = null)
    // {
    //     $data = parent::validated();

    //     if (isset($data['vender_details']) && is_array($data['vender_details'])) {
    //         $data['vender_details'] = implode(',', $data['vender_details']);
    //     }

    //     return $data;
    // }
}
