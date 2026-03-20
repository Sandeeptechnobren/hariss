<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;


class UpdateCapsCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id'   => 'nullable|integer|exists:tbl_warehouse,id',
            'route_id'       => 'nullable|integer|exists:tbl_route,id',
            'salesman_id'    => 'nullable|integer|exists:salesman,id',
            'customer'       => 'nullable|string',
            'status'         => 'nullable|integer|min:0',

            'details'                     => 'nullable|array|min:1',
            'details.*.item_id'           => 'nullable|integer|exists:items,id',
            'details.*.uom_id'            => 'nullable|integer|exists:item_uoms,id',
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