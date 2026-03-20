<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

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
            'unload_no' => 'required|string|max:50',
            'unload_date' => 'nullable|date',
            'load_date' => 'nullable|date',
            'unload_time' => 'nullable',
            'sync_date'=> 'nullable|date',
            'sync_time'=> 'nullable',
            'warehouse_id' => 'required|integer',
            'route_id' => 'nullable|integer',
            'salesman_id' => 'required|integer',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'salesman_type' => 'nullable|Integer',
            'project_type' => 'nullable|integer|exists:project_list,id',
            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.uom' => 'nullable|integer',
            'details.*.qty' => 'required|numeric',
        ];
    }
protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
