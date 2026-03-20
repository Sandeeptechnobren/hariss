<?php

namespace App\Http\Requests\V1\Claim_Management\Web;

use Illuminate\Foundation\Http\FormRequest;

class PetitClaimRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "osa_code" => "nullable|string|max:50",

            "claim_type" => "nullable|in:1,2",  // ENUM: 1 = petit, 2 = commission

            "warehouse_id" => "required|integer",
            "petit_name" => "nullable|string|max:255",

            "fuel_amount" => "nullable|numeric",
            "rent_amount" => "nullable|numeric",
            "agent_amount" => "nullable|numeric",

            "month_range" => "required|string|max:100",
            "year" => "required|string|max:255",
            "status" => "nullable|integer",

            "approver_id" => "nullable|integer",
            "action_date" => "nullable|date",

            "customercare_id" => "nullable|integer",
            "care_actiondate" => "nullable|date",
            "care_comment" => "nullable|string|max:300",
            "claim_file" => "nullable|file|mimes:jpg,jpeg,png,pdf|max:4096",

            "reject_reason" => "nullable|string",

            "created_user" => "nullable|integer",
            "updated_user" => "nullable|integer",
        ];
    }
}
