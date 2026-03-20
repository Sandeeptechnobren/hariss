<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallRegisterUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            "osa_code" => "sometimes|nullable|string|max:50",

            "ticket_type"     => "sometimes|string|max:20",
            "ticket_date"     => "sometimes|date",
            "technician_id"   => "sometimes|integer",
            "sales_valume"    => "sometimes|nullable|integer",
            "ctc_status"      => "sometimes|integer",

            "chiller_serial_number" => "sometimes|nullable|string|max:200",
            "asset_number"          => "sometimes|nullable|string|max:200",
            "model_number"          => "sometimes|nullable|string|max:200",
            // "chiller_code"          => "sometimes|nullable|string|max:100",
            "branding"              => "sometimes|nullable|string|max:200",
            "assigned_customer_id"  => "sometimes|nullable|integer",

            "current_outlet_code"   => "sometimes|nullable|string|max:200",
            "current_outlet_name"   => "sometimes|nullable|string|max:200",
            "current_owner_name"    => "sometimes|nullable|string|max:50",
            "current_road_street"   => "sometimes|nullable|string|max:100",
            "current_town"          => "sometimes|nullable|string|max:100",
            "current_landmark"      => "sometimes|nullable|string|max:100",
            "current_district"      => "sometimes|nullable|string|max:100",
            "current_contact_no1"   => "sometimes|string|max:50",
            "current_contact_no2"   => "sometimes|nullable|string|max:50",

            "current_warehouse" => "sometimes|nullable|integer",
            "current_asm"       => "sometimes|nullable|integer",
            "current_rm"        => "sometimes|nullable|integer",

            "nature_of_call"    => "sometimes|string|max:500",
            "follow_up_action"  => "sometimes|string|max:500",
            "followup_status"   => "sometimes|nullable|string|max:500",
            "status"            => "sometimes|string|max:100",

            "call_category"        => "sometimes|nullable|string|max:255",
            "reason_for_cancelled" => "sometimes|nullable|string|max:100",

            "customer_id" => "sometimes|nullable|integer",
            "fridge_id"   => "sometimes|nullable|integer",
        ];
    }
}
