<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallRegisterStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "osa_code" => [
                'required',
                'string',
                Rule::unique('tbl_call_register', 'osa_code')->ignore($this->id),
            ],
            "ticket_type"        => "required|string|max:20",
            "ticket_date"        => "required|date",
            "technician_id"      => "required|integer",
            "sales_valume"       => "nullable|integer",
            "ctc_status"         => "required|integer",

            "chiller_serial_number" => "nullable|string|max:200",
            "asset_number"          => "nullable|string|max:200",
            "model_number"          => "nullable|string|max:200",
            // "chiller_code"          => "nullable|integer",
            "branding"              => "nullable|string|max:200",
            "assigned_customer_id"  => "nullable|integer",

            "current_outlet_code"   => "nullable|string|max:200",
            "current_outlet_name"   => "nullable|string|max:200",
            "current_owner_name"    => "nullable|string|max:50",
            "current_road_street"   => "nullable|string|max:100",
            "current_town"          => "nullable|string|max:100",
            "current_landmark"      => "nullable|string|max:100",
            "current_district"      => "nullable|string|max:100",
            "current_contact_no1"   => "required|string|max:50",
            "current_contact_no2"   => "nullable|string|max:50",

            "current_warehouse" => "nullable|integer",
            "current_asm"       => "nullable|integer",
            "current_rm"        => "nullable|integer",

            "nature_of_call"    => "required|string|max:500",
            "follow_up_action"  => "required|string|max:500",
            "followup_status"   => "nullable|string|max:500",
            "status"            => "required|string|max:100",

            "call_category"         => "nullable|string|max:255",
            "reason_for_cancelled"  => "nullable|string|max:255",

            "customer_id"      => "nullable|integer",
            "fridge_id"        => "nullable|integer"
        ];
    }
}
