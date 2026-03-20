<?php

namespace App\Http\Requests\V1\Loyality_Management;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code'     => 'nullable|string|unique:tbl_adjustment,osa_code',
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'route_id'     => 'required|integer|exists:tbl_route,id',
            'customer_id'  => 'required|integer|exists:agent_customers,id',
            'currentreward_points' => 'nullable|integer',
            'adjustment_points'    => 'required|integer',
            'closing_points'       => 'nullable|integer',
            'adjustment_symbol'    => 'required|integer|in:1,2',
            'description'          => 'required|string',

        ];
    }
}