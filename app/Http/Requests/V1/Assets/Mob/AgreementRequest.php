<?php

namespace App\Http\Requests\V1\Assets\Mob;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'osa_code' => 'nullable|string|max:100',
            'ms' => 'required|string|max:300',
            'ms_of' => 'required|string|max:300',
            'address' => 'required|string',

            'asset_number' => 'nullable|string|max:200',
            'serial_number' => 'nullable|string|max:200',
            'model_branding' => 'nullable|string|max:200',

            'behaf_hariss_name_contact' => 'nullable|string|max:500',
            'behaf_hariss_sign' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'behaf_reciver_old_signature' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'behaf_hariss_date' => 'nullable|date',

            'behaf_reciver_name_contact' => 'nullable|string|max:200',
            'behaf_reciver_sign' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'behaf_reciver_date' => 'nullable|date',

            'presence_sales_name' => 'nullable|string|max:200',
            'presence_sales_contact' => 'nullable|string|max:50',
            'presence_sign' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',

            'presence_lc_name' => 'nullable|string|max:200',
            'presence_lc_contact' => 'nullable|string|max:50',
            'presence_lc_sign' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',

            'presence_landloard_name' => 'nullable|string|max:200',
            'presence_landloard_contact' => 'nullable|string|max:50',
            'presence_landloard_sign' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',

            'salesman_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'fridge_id' => 'nullable|integer',
            'ir_id' => 'nullable|integer',
            'add_chiller_id' => 'nullable|integer',

            'installed_img1' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'installed_img2' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'installed_img3' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }
}

