<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChillerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // vendor_details ko array me convert kar lo
        if ($this->filled('vender_details')) {
            $details = is_array($this->vender_details)
                ? $this->vender_details
                : explode(',', $this->vender_details);
            
            $this->merge([
                'vender_details' => array_map('trim', $details)
            ]);
        }
    }

    public function rules()
    {
        $id = null;

        // Update ke case me id fetch karo
        if ($this->route('uuid')) {
            $chiller = \App\Models\AddChiller::where('uuid', $this->route('uuid'))->first();
            $id = $chiller ? $chiller->id : null;
        }

        return [
            'fridge_code' => [
                'sometimes',
                'string',
                'max:200',
                Rule::unique('tbl_add_chillers', 'fridge_code')->ignore($id)
            ],
            'serial_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('tbl_add_chillers', 'serial_number')->ignore($id)
            ],
            'asset_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('tbl_add_chillers', 'asset_number')->ignore($id)
            ],
            'model_number' => 'sometimes|string|max:50',
            'description'  => 'nullable|string',
            'acquisition'  => 'nullable|string|max:50',
            'vender_details' => 'nullable|array',
            'vender_details.*' => 'integer|exists:tbl_vendor,id',
            'manufacturer' => 'nullable|string|max:200',
            'country_id'   => 'sometimes|integer|exists:tbl_country,id',
            'type_name'    => 'nullable|string|max:100',
            'sap_code'     => 'nullable|string|max:50',
            'status'       => 'integer|in:0,1',
            'is_assign'    => 'integer|in:0,1,2',
            'customer_id'  => 'sometimes|integer',
            'agreement_id' => 'nullable|integer',
            'document_type' => 'nullable|in:ACF,CRF',
            'document_id'  => 'nullable|integer',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        
        if (isset($data['vender_details']) && is_array($data['vender_details'])) {
            $data['vender_details'] = implode(',', $data['vender_details']);
        }

        return $data;
    }
}
