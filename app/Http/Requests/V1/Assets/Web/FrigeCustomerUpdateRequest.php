<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class FrigeCustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            // Basic Info
            'osa_code' => 'nullable|string',
            'outlet_name' => 'nullable|string',
            'owner_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'landmark' => 'nullable|string',
            'outlet_type' => 'nullable|string',
            'existing_coolers' => 'nullable|string',
            'outlet_weekly_sale_volume' => 'nullable|string',
            'display_location' => 'nullable|string',
            'chiller_safty_grill' => 'nullable|integer',
            'agent' => 'nullable|string',
            'manager_sales_marketing' => 'nullable|integer',

            // Machine Info
            'national_id' => 'nullable|string',
            'outlet_stamp' => 'nullable|string',
            'model' => 'nullable|integer',
            'hil' => 'nullable|string',
            'ir_reference_no' => 'nullable|string',
            'installation_done_by' => 'nullable|string',
            'date_lnitial' => 'nullable|date',
            'date_lnitial2' => 'nullable|date',
            'contract_attached' => 'nullable|integer',
            'machine_number' => 'nullable|string',
            'brand' => 'nullable|string',
            'asset_number' => 'nullable|string',
            'lc_letter' => 'nullable|string',
            'trading_licence' => 'nullable|string',
            'password_photo' => 'nullable|string',
            'outlet_address_proof' => 'nullable|string',

            // Managers
            'chiller_asset_care_manager' => 'nullable|integer',
            'sales_marketing_director' => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
            'area_manager' => 'nullable|string',

            // Sales / Routing
            'customer_id' => 'nullable|integer',
            'sales_excutive' => 'nullable|string',
            'salesman_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',

            // Outlet Details
            'name_contact_of_the_customer' => 'nullable|string',
            'chiller_size_requested' => 'nullable|string',
            'outlet_weekly_sales' => 'nullable|string',
            'stock_share_with_competitor' => 'nullable|string',
            'specify_if_other_type' => 'nullable|string',
            'location' => 'nullable|string',
            'postal_address' => 'nullable|string',
            'serial_no' => 'nullable|string',

            // Office
            'fridge_office_id' => 'nullable|integer',
            'fridge_maanger_id' => 'nullable|integer',

            // Status
            'status' => 'nullable|integer',
            'request_document_status' => 'nullable|integer',
            'agreement_id' => 'nullable|integer',
            'fridge_status' => 'nullable|integer',
            'remark' => 'nullable|string',

            // File Fields
            'national_id_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'password_photo_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_address_proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'trading_licence_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'lc_letter_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_stamp_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'sign__customer_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'national_id1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'password_photo1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_address_proof1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'trading_licence1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'lc_letter1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_stamp1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'sign_salesman_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'fridge_scan_img' => 'nullable|file|mimes:jpg,jpeg,png',
        ];
    }
}
