<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UnloadHeaderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unload_no' => 'sometimes|string|max:50',
            'unload_date' => 'sometimes|date',
            'unload_time' => 'sometimes',
            'warehouse_id' => 'sometimes|integer',
            'route_id' => 'sometimes|integer',
            'salesman_id' => 'sometimes|integer',
            'latitude' => 'nullable|numeric',
            'longtitude' => 'nullable|numeric',
            'salesman_type' => 'sometimes|string',
            'project_type' => 'sometimes|integer|exists:project_list,id',
            'unload_from' => 'nullable|string|max:50',
            'load_date' => 'nullable|date',
            'status' => 'nullable|smallint',

            // details array for update
            'details' => 'nullable|array|min:1',
            'details.*.item_id' => 'sometimes|integer',
            'details.*.uom' => 'sometimes|integer',
            'details.*.qty' => 'sometimes|numeric|min:0',
            'details.*.status' => 'sometimes|smallint',
        ];
    }
}
