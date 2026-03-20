<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteExpenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salesman_id' => 'sometimes|exists:salesman,id',
            'warehouse_id' => 'sometimes|exists:tbl_warehouse,id',
            'route_id' => 'sometimes|exists:tbl_route,id',
            'expence_type' => 'sometimes|exists:tbl_expence_type,id',
            'description' => 'sometimes|string',
            'image' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|boolean',
        ];
    }
}
