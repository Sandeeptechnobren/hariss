<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;


class StoreCapsCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => 'required|string|unique:caps_collection_headers,code',
            'warehouse_id'   => 'required|integer|exists:tbl_warehouse,id',
            'route_id'       => 'nullable|integer|exists:tbl_route,id',
            'salesman_id'    => 'nullable|integer|exists:salesman,id',
            'customer'       => 'required|integer',
            'status'         => 'nullable|integer|min:0',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            'details'                     => 'required|array|min:1',
            'details.*.item_id'           => 'required|integer|exists:items,id',
            'details.*.uom_id'            => 'required|integer|exists:uom,id',
            'details.*.collected_quantity'=> 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'details.required'             => 'A caps collection must have at least one item.',
            'details.*.item_id.required'   => 'Each collection detail must have an item.',
            'details.*.item_id.exists'     => 'One or more selected items do not exist.',
            'details.*.uom_id.required'    => 'Each collection detail must have a valid UOM.',
            'details.*.uom_id.exists'      => 'One or more UOMs are invalid.',
            'warehouse_id.exists'          => 'The selected warehouse does not exist.',
            'route_id.exists'              => 'The selected route does not exist.',
            'salesman_id.exists'           => 'The selected salesman does not exist.',
        ];
    }
}