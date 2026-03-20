<?php

namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;

class AddChillerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

     public function rules(): array
    {
         return [
            'osa_code' => 'nullable|string|max:50',

            'outlet_name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
            'landmark' => 'nullable|string|max:255',
            'outlet_type' => 'nullable|integer',
            'existing_coolers' => 'nullable|string|max:100',
            'outlet_weekly_sale_volume' => 'nullable|string|max:100',
            'display_location' => 'nullable|string|max:255',
            'chiller_safty_grill' => 'nullable|in:0,1,true,false',

            'agent' => 'nullable|string|max:255',
            'manager_sales_marketing' => 'nullable|string|max:255',

            'national_id' => 'nullable|string|max:100',
            'outlet_stamp' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'hil' => 'nullable|string|max:100',
            'ir_reference_no' => 'nullable|string|max:100',
            'installation_done_by' => 'nullable|string|max:100',

            'date_lnitial' => 'nullable|date',
            'date_lnitial2' => 'nullable|date',

            'contract_attached' => 'nullable|in:0,1,true,false',
            'machine_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'asset_number' => 'nullable|string|max:100',
            'lc_letter' => 'nullable|string|max:100',
            'trading_licence' => 'nullable|string|max:100',
            'password_photo' => 'nullable|string|max:100',
            'outlet_address_proof' => 'nullable|string|max:100',
            'chiller_asset_care_manager' => 'nullable|integer|max:100',

            // 🔽 FILE VALIDATIONS
            'national_id_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'password_photo_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'outlet_address_proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'trading_licence_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'lc_letter_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_stamp_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'sign__customer_file' => 'nullable|file|mimes:jpg,jpeg,png',

            'national_id1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'password_photo1_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'outlet_address_proof1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'trading_licence1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'lc_letter1_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'outlet_stamp1_file' => 'nullable|file|mimes:jpg,jpeg,png',

            'sales_marketing_director' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|integer',
            'area_manager' => 'nullable|string|max:255',
            'name_contact_of_the_customer' => 'nullable|string|max:255',

            'chiller_size_requested' => 'nullable|string|max:100',
            'outlet_weekly_sales' => 'nullable|string|max:100',
            'stock_share_with_competitor' => 'nullable|string|max:255',
            'specify_if_other_type' => 'nullable|string|max:255',

            'location' => 'nullable|string|max:255',
            'postal_address' => 'nullable|string|max:255',
            'customer_id' => 'nullable|integer',

            'sales_excutive' => 'nullable|string|max:255',
            'salesman_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',

            'sign_salesman_file' => 'nullable|file|mimes:jpg,jpeg,png',
            'serial_no' => 'nullable|string|max:100',
            'fridge_scan_img' => 'nullable|file|mimes:jpg,jpeg,png',
            'fridge_office_id' => 'nullable|integer',
            'fridge_maanger_id' => 'nullable|integer',

            'status' => 'required|in:0,1,true,false',
            'request_document_status' => 'nullable|integer|max:50',
            'agreement_id' => 'nullable|integer',
            'fridge_status' => 'nullable|integer|max:50',

            'remark' => 'nullable|string',
        ];
    }
}
