<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class ChillerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'sometimes|string|max:200|unique:chillers,osa_code,' . $this->id,
            'owner_name' => 'sometimes|string|max:200',
            'status' => 'nullable|integer|in:0,1',
            'fridge_status' => 'nullable|integer',
            'customer_id'  => 'nullable|integer|exists:agent_customer,id',
            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
            'salesman_id' => 'nullable|integer|exists:salesman,id',
            'outlet_id' => 'nullable|integer|exists:outlet_channel,id',

            'outlet_name' => 'nullable|string|max:255',
            'chiller_size_requested' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'existing_coolers' => 'nullable|string|max:255',
            'outlet_weekly_sale_volume' => 'nullable|string|max:20',
            'outlet_weekly_sale_volume_current' => 'nullable|string|max:20',
            'display_location' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'chiller_safty_grill' => 'nullable|string|max:255',
            'manager_sales_marketing' => 'nullable|integer',
            'outlet_stamp' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'hil' => 'nullable|string|max:255',
            'ir_reference_no' => 'nullable|string|max:255',
            'installation_done_by' => 'nullable|string|max:255',
            'date_lnitial' => 'nullable|string|max:255',
            'date_lnitial2' => 'nullable|string|max:255',
            'contract_attached' => 'nullable|string|max:255',
            'machine_number' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'lc_letter' => 'nullable|string|max:255',
            'chiller_asset_care_manager' => 'nullable|integer',
            'stock_share_with_competitor' => 'nullable|integer',
            'national_id' => 'nullable|string|max:255',
            'trading_licence' => 'nullable|string|max:255',
            'password_photo' => 'nullable|string|max:255',
            'outlet_address_proof' => 'nullable|string|max:255',

            'password_photo_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'lc_letter_file'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'trading_licence_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_stamp_file'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_address_proof_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'sign__customer_file'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'national_id_file'     => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'chiller_manager_id' => 'nullable|integer',
            'is_merchandiser' => 'nullable|integer|in:0,1',
            'iro_id' => 'nullable|integer',
            'remark' => 'nullable|string',
        ];
    }
}
