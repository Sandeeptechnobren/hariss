<?php

namespace App\Http\Requests\V1\Loyality_Management;

use Illuminate\Foundation\Http\FormRequest;

class LoyalityPointRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code'     => 'nullable|string|unique:customerloyality_points,osa_code',
            'customer_id'  => 'required|integer|exists:agent_customers,id',
            'tier_id'      => 'required|integer|exists:tbl_tiers,id',
            'total_earning' => 'nullable|integer',
            'total_spend'   => 'nullable|integer',
            'total_closing' => 'nullable|integer',

            'details'                       => 'required|array|min:1',
            'details.*.osa_code'            => 'nullable|string|unique:customerloyality_activity,osa_code',
            'details.*.activity_date'       => 'required|date',
            'details.*.activity_type'       => 'required|string|in:earn,redeem,adjustment',
            'details.*.invoice_id'          => 'required|array',
            'details.*.invoice_id.*'        => 'integer',
            'details.*.description'         => 'nullable|string',
            'details.*.incooming_point'     => 'required|integer',
            'details.*.outgoing_point'      => 'nullable|integer',
            'details.*.closing_point'       => 'nullable|integer',
            'details.*.adjustment_point'    => 'nullable|integer',
            'details.*.promotion'           => 'nullable|boolean',
            'details.*.net'                 => 'nullable|numeric',
            'details.*.excise'              => 'nullable|numeric',
            'details.*.pre_vat'             => 'nullable|numeric',
            'details.*.vat'                 => 'nullable|numeric',
            'details.*.total'               => 'nullable|numeric',
        ];
    }
}
