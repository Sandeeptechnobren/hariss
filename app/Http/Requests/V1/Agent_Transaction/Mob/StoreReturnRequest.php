<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => 'nullable|string|max:55',
            'country_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'delivery_id' => 'nullable|integer',
            'warehouse_id' => 'required|integer',
            'route_id' => 'nullable|integer',
            'customer_id' => 'required|integer',
            'salesman_id' => 'nullable|integer',
            'gross_total' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|integer|min:0',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.uom_id' => 'required|integer|exists:item_uoms,uom_id',
            'details.*.item_price' => 'required|numeric|min:0',
            'details.*.item_quantity' => 'required|numeric|min:0.001',
            'details.*.vat' => 'nullable|numeric|min:0',
            'details.*.discount' => 'nullable|numeric|min:0',
            'details.*.gross_total' => 'nullable|numeric|min:0',
            'details.*.net_total' => 'nullable|numeric|min:0',
            'details.*.return_type' => 'nullable|integer',
            'details.*.return_reason' => 'nullable|integer',
            'details.*.total' => 'nullable|numeric|min:0',
            'details.*.is_promotional' => 'nullable|boolean',
            'details.*.discount_id' => 'nullable|integer',

            'details.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
            'details.*.parent_id' => 'nullable|integer|',
            'details.*.status' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'details.required' => 'A return must have at least one item.',
            'details.*.item_id.exists' => 'One or more items in the return do not exist.',
            'details.*.uom_id.exists' => 'One or more UOMs in the return are invalid.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'route_id.exists' => 'The selected route does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
        ];
    }
}