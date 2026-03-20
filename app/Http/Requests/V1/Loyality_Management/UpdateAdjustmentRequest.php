<?php

namespace App\Http\Requests\V1\Loyality_Management;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdjustmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code'     => 'nullable|string|unique:tbl_adjustment,osa_code',
            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
            'route_id'     => 'nullable|integer|exists:tbl_route,id',
            'customer_id'  => 'nullable|integer|exists:agent_customers,id',
            'currentreward_points' => 'nullable|integer',
            'adjustment_points'    => 'nullable|integer',
            'closing_points'       => 'nullable|integer',
            'adjustment_symbol'    => 'nullable|integer|in:1,0',

        ];
    }
}