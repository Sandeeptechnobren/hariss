<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class VisitPlanUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'salesman_id' => 'sometimes|integer',
            'customer_id' => 'sometimes|integer',
            'warehouse_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'visit_start_time' => 'nullable|date',
            'visit_end_time' => 'nullable|date',
            'shop_status' => 'nullable|in:0,1',
            'remark' => 'nullable|string'
        ];
    }
}
