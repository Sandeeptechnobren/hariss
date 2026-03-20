<?php

namespace App\Http\Requests\V1\Loyality_Management;

use Illuminate\Foundation\Http\FormRequest;

class LoyalityPointUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code'      => 'nullable|string',
            'customer_id'   => 'nullable|integer|exists:agent_customers,id',
            'total_earning' => 'nullable|numeric',
            'total_spend'   => 'nullable|numeric',
            'total_closing' => 'nullable|numeric',
            'tier_id'       => 'nullable|integer|exists:tbl_tiers,id',

            'details'                       => 'nullable|array',
            'details.*.osa_code'            => 'nullable|string',
            'details.*.customer_id'         => 'nullable|integer|exists:agent_customers,id',
            'details.*.activity_date'       => 'nullable|date',
            'details.*.activity_type'       => 'nullable|string|in:earn,reedem,adjustment',
            'details.*.record_id'           => 'nullable|integer',
            'details.*.description'         => 'nullable|string',
            'details.*.incoming_point'      => 'nullable|numeric',
            'details.*.outgoing_point'      => 'nullable|numeric',
            'details.*.closing_point'       => 'nullable|numeric'
        ];
    }

    public function messages()
    {
        return [
            'details.*.customer_id.required' => 'Customer ID is required for detail.',
            'details.*.activity_date.required' => 'Activity date is required.',
            'details.*.activity_type.required' => 'Activity type is required.',
            'details.*.record_id.required' => 'Record ID is required.',
        ];
    }
}
