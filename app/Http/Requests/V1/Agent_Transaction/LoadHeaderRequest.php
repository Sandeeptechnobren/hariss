<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class LoadHeaderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|integer',
            'route_id' => 'required|integer',
            'salesman_id' => 'required|integer|exists:salesman,id',
            'is_confirmed' => 'nullable|boolean',
            'accept_time' => 'nullable|date',
            'salesman_sign' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longtitude' => 'nullable|numeric',
            'salesman_type' => 'nullable|integer',
            'project_type' => 'nullable|integer|exists:project_list,id',
            'status' => 'nullable|integer|in:0,1',
            'details' => 'nullable|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.uom' => 'nullable|integer|exists:uom,id',
            'details.*.qty' => 'required|integer',
            'details.*.price' => 'nullable|numeric',
            'details.*.status' => 'nullable|integer|in:0,1',
            'details.*.unload_status' => 'nullable|integer|in:0,1'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
