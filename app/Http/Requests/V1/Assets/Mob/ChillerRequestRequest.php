<?php

namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChillerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:200',
            'status' => 'nullable|integer|in:0,1',
            'fridge_status' => 'nullable|integer',
            'customer_id'  => 'nullable|integer|exists:agent_customers,id',
            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
            'salesman_id' => 'nullable|integer|exists:salesman,id',
            'outlet_id' => 'nullable|integer|exists:outlet_channel,id',

            'outlet_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'existing_coolers' => 'nullable|string|max:255',
            'outlet_weekly_sale_volume' => 'nullable|string|max:20',
            'display_location' => 'nullable|string|max:255',
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
            'postal_address' => 'nullable|string|max:255',
            'chiller_asset_care_manager' => 'nullable|integer',
            'stock_share_with_competitor' => 'nullable|integer',
            'national_id' => 'nullable|string|max:255',
            'trading_licence' => 'nullable|string|max:255',
            'password_photo' => 'nullable|string|max:255',
            'outlet_address_proof' => 'nullable|string|max:255',
            'chiller_size_requested' => 'nullable|string|max:255',

            'password_photo_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'lc_letter_file'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'trading_licence_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_stamp_file'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'outlet_address_proof_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'sign__customer_file'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'national_id_file'     => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'national_id1_file'    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'chiller_manager_id' => 'nullable|integer',
            'is_merchandiser' => 'nullable|integer|in:0,1',
            'iro_id' => 'nullable|integer',
            'remark' => 'nullable|string',
        ];
    }
public function messages(): array
{
    return [
        'osa_code.unique' => 'OSA Code already exists.',
        'osa_code.max' => 'OSA Code maximum 50 characters allowed.',
        
        'customer_id.exists' => 'Selected customer does not exist.',
        'warehouse_id.exists' => 'Selected warehouse does not exist.',
        'salesman_id.exists' => 'Selected salesman does not exist.',
        'outlet_id.exists' => 'Selected outlet does not exist.',

        'status.in' => 'Status must be 0 or 1 only.',
        'is_merchandiser.in' => 'Merchandiser value must be 0 or 1.',

        'password_photo_file.mimes' => 'Password photo must be jpeg, png, jpg or pdf.',
        'password_photo_file.max' => 'Password photo size must not exceed 5MB.',

        'lc_letter_file.mimes' => 'LC Letter must be jpeg, png, jpg or pdf.',
        'trading_licence_file.mimes' => 'Trading licence must be jpeg, png, jpg or pdf.',
        'national_id_file.mimes' => 'National ID must be jpeg, png, jpg or pdf.',
    ];
}
protected function failedValidation(Validator $validator)
{
    throw new HttpResponseException(
        response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422)
    );
}
}
