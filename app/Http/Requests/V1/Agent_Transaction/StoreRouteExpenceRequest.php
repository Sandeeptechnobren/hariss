<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteExpenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salesman_id' => 'required|exists:salesman,id',
            'warehouse_id' => 'required|exists:tbl_warehouse,id',
            'route_id' => 'required|exists:tbl_route,id',
            'expence_type' => 'required|exists:tbl_expence_type,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
        ];
    }
}
