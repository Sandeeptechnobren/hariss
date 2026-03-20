<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UnloadHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unload_no' => 'nullable|string|max:50',
            'unload_date' => 'nullable|date',
            'load_date' => 'nullable|date',
            'unload_time' => 'nullable',
            'warehouse_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
            'salesman_id' => 'nullable|integer',
            'latitude' => 'nullable|string',
            'longtitude' => 'nullable|string',
            'salesman_type' => 'nullable|string',
            'project_type' => 'nullable|integer|exists:project_list,id',
            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.uom' => 'nullable|integer',
            'details.*.qty' => 'required|integer',
        ];
    }
}
