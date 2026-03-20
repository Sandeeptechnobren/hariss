<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class LoadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'header_osa_code' => 'nullable|string',
            'warehouse_id' => 'required|integer',
            'route_id' => 'required|integer',
            'salesman_id' => 'required|integer',
            'is_confirmed' => 'required|integer',
            'details' => 'required|array|min:1',
            'details.*.osa_code' => 'nullable|string',
            'details.*.item_id' => 'required|integer',
            'details.*.uom' => 'required|integer',
            'details.*.qty' => 'required|numeric|min:1',
            'details.*.price' => 'required|numeric|min:0',
        ];
    }
}