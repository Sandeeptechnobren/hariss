<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class LoadHeaderUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => 'sometimes|integer',
            'route_id' => 'sometimes|integer',
            'salesman_id' => 'sometimes|integer|exists:salesman,id',
            'is_confirmed' => 'sometimes|boolean',
            'accept_time' => 'sometimes|date',
            'salesman_sign' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric',
            'longtitude' => 'sometimes|numeric',
            'salesman_type' => 'sometimes|integer',
            'project_type' => 'sometimes|integer',
            'status' => 'sometimes|integer|in:0,1',
            'details' => 'sometimes|array|min:1',
            'details.*.item_id' => 'sometimes|integer|exists:items,id',
            'details.*.uom' => 'sometimes|integer|exists:item_uoms,uom_id',
            'details.*.qty' => 'sometimes|integer',
            'details.*.price' => 'sometimes|numeric',
            'details.*.status' => 'sometimes|integer|in:0,1'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
