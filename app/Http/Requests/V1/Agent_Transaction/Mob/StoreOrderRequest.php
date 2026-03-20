<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => 'nullable|string|max:10',
            'country_id' => '  nullable|integer|max:100',
            'order_code' => 'required|string|unique:agent_order_headers,order_code',
            
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'route_id' => 'nullable|integer|exists:tbl_route,id',
            'customer_id' => 'required|integer|exists:agent_customers,id',
            'salesman_id' => 'nullable|integer|exists:salesman,id',

            'delivery_date' => 'required|date|after_or_equal:today',
            'gross_total' => 'nullable|numeric|min:0',
            'vat' => 'required|numeric|min:0',
            'net_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'comment' => 'nullable|string|max:2000',
            'status'=> 'required|in:0,1',
            'order_flag' => 'nullable|in:1,2,3',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.item_price' => 'required|numeric|min:0',
            'details.*.quantity' => 'required|numeric|min:0.001',
            'details.*.vat' => 'required|numeric|min:0',
            'details.*.uom_id' => 'required|integer|exists:uom,id',
            'details.*.discount' => 'nullable|numeric|min:0',
            'details.*.discount_id' => 'nullable|integer|exists:discounts,id',
            'details.*.gross_total' => 'nullable|numeric|min:0',
            'details.*.net_total' => 'required|numeric|min:0',
            'details.*.total' => 'required|numeric|min:0',
            'details.*.is_promotional' => 'nullable|boolean',
            'details.*.parent_id' => 'nullable|integer|exists:agent_order_details,id',
            'details.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'order_number.unique' => 'This order number already exists.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'details.required' => 'Order must have at least one item.',
            'details.*.item_id.exists' => 'One or more items do not exist.',
        ];
    }
}
