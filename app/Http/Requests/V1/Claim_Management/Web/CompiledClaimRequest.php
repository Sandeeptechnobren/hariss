<?php

namespace App\Http\Requests\V1\Claim_Management\Web;

use Illuminate\Foundation\Http\FormRequest;

class CompiledClaimRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "osa_code" => "nullable|string|max:50",
            "claim_period" => "nullable|string|max:100",
            "warehouse_id" => "required|string|max:150",

            "approved_qty_cse" => "nullable|numeric",
            "approved_claim_amount" => "nullable|numeric",
            "rejected_qty_cse" => "nullable|numeric",
            "rejected_amount" => "nullable|numeric",

            "area_sales_supervisor" => "nullable|string|max:150",
            "regional_sales_manager" => "nullable|string|max:150",

            "month_range" => "nullable|string|max:100",
            "promo_count" => "nullable|integer",
            "promo_qty" => "nullable|string",
            "promo_amount" => "nullable|numeric",
            "reject_qty" => "nullable|string",
            "rejecte_amount" => "nullable|numeric",

            "agent_id" => "nullable|integer",
            "agent_actiondate" => "nullable|date",
            "supervisor_id" => "nullable|integer",
            "asm_actiondate" => "nullable|date",
            "manager_id" => "nullable|integer",
            "manger_actiondate" => "nullable|date",
            "start_date" => "nullable|string",
            "end_date" => "nullable|string",
            "rejected_reason" => "nullable|string",

            "status" => "nullable|integer",

            "verifier_id" => "nullable|integer",
            "reject_comment" => "nullable|string|max:255",

            "asm_comment" => "nullable|string|max:300",
            "rm_comment" => "nullable|string|max:300",
            "agent_comment" => "nullable|string|max:300",

            "created_user" => "nullable|integer",
            "updated_user" => "nullable|integer",
        ];
    }
}
