<?php

namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChillerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic info
            'osa_code' => 'sometimes|string|max:200|unique:chiller_requests,osa_code,' . $this->uuid . ',uuid',
            'outlet_name' => 'sometimes|string|max:255',
            'owner_name' => 'sometimes|string|max:200',
            'customer_id' => 'sometimes|integer|exists:agent_customers,id',
            'warehouse_id' => 'sometimes|integer|exists:tbl_warehouse,id',
            'salesman_id' => 'sometimes|integer|exists:salesman,id',
            'outlet_id' => 'sometimes|integer|exists:outlet_channel,id',

            // Contact & location
            'contact_number' => 'sometimes|string|max:255',
            'landmark' => 'sometimes|string|max:255',
            'outlet_type' => 'sometimes|string|max:50',
            'existing_coolers' => 'sometimes|string|max:255',
            'outlet_weekly_sale_volume' => 'sometimes|string|max:20',
            'display_location' => 'sometimes|string|max:255',
            'chiller_safty_grill' => 'sometimes|string|max:255',

            // Manager info
            'manager_sales_marketing' => 'sometimes|integer',
            'chiller_asset_care_manager' => 'sometimes|integer',
            'stock_share_with_competitor' => 'sometimes|integer',

            // Asset / documents
            'national_id' => 'sometimes|string|max:255',
            'outlet_stamp' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'hil' => 'sometimes|string|max:255',
            'ir_reference_no' => 'sometimes|string|max:255',
            'installation_done_by' => 'sometimes|string|max:255',
            'date_lnitial' => 'sometimes|date',
            'date_lnitial2' => 'sometimes|date',
            'contract_attached' => 'sometimes',
            'machine_number' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'asset_number' => 'sometimes|string|max:255',
            'lc_letter' => 'sometimes|string|max:255',
            'trading_licence' => 'sometimes|string|max:255',
            'password_photo' => 'sometimes|string|max:255',
            'outlet_address_proof' => 'sometimes|string|max:255',

            // File uploads (for update, we allow either string or file)
            'national_id_file' => 'sometimes',
            'password_photo_file' => 'sometimes',
            'outlet_address_proof_file' => 'sometimes',
            'trading_licence_file' => 'sometimes',
            'lc_letter_file' => 'sometimes',
            'outlet_stamp_file' => 'sometimes',
            'sign__customer_file' => 'sometimes',

            // Additional fields
            'chiller_manager_id' => 'sometimes|integer',
            'is_merchandiser' => 'sometimes|integer|in:0,1',
            'status' => 'sometimes|integer|in:0,1',
            'fridge_status' => 'sometimes|integer|in:0,1',
            'iro_id' => 'sometimes|integer',
            'remark' => 'sometimes|string|max:500',
        ];
    }
}