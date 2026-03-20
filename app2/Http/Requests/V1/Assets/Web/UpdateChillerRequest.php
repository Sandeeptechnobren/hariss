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

    protected function prepareForValidation()
    {
        if ($this->filled('vender_details')) {
            $details = is_array($this->vender_details)
                ? $this->vender_details
                : explode(',', $this->vender_details);

            $this->merge([
                'vender_details' => array_map('trim', $details)
            ]);
        }
    }

    public function rules(): array
    {
        $chiller = AddChiller::where('uuid', $this->route('uuid'))->first();
        $id = $chiller ? $chiller->id : null;

        return [
            'fridge_code' => ['sometimes', 'string', 'max:200', Rule::unique('add_chiller', 'fridge_code')->ignore($id)],
            'serial_number' => ['sometimes', 'string', 'max:50', Rule::unique('add_chiller', 'serial_number')->ignore($id)],
            'asset_number' => ['sometimes', 'string', 'max:50', Rule::unique('add_chiller', 'asset_number')->ignore($id)],
            'model_number' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'acquisition' => 'nullable|string|max:50',
            'vender_details' => 'nullable|array',
            'vender_details.*' => 'integer|exists:tbl_vendor,id',
            'manufacturer' => 'nullable|string|max:200',
            'country_id' => 'sometimes|integer|exists:tbl_country,id',
            'type_name' => 'nullable|string|max:100',
            'sap_code' => 'nullable|string|max:50',
            'status' => 'sometimes|integer|in:0,1',
            'is_assign' => 'sometimes|integer|in:0,1,2',
            'customer_id' => 'sometimes|integer|exists:tbl_customer,id',
            'warehouse_id' => 'sometimes|integer|exists:tbl_warehouse,id',
            'salesman_id' => 'sometimes|integer|exists:salesman,id',
            'outlet_id' => 'nullable|integer|exists:outlet_channel,id',
            'agreement_id' => 'nullable|integer',
            'document_type' => 'nullable|in:ACF,CRF',
            'document_id' => 'nullable|integer',

            // 🔹 File fields
            'password_photo_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'lc_letter_file'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'trading_licence_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_stamp_file'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_address_proof_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'sign__customer_file'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'national_id_file'     => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',

            // 🔹 Add any other extra fields you need to allow updating
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
