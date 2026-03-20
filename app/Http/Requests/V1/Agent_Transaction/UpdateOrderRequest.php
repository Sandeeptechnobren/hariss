<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // public function rules(): array
    // {
    //     $orderId = $this->route('id');
    //     return [
    //         'currency' => 'sometimes|required|string|max:10',
    //         'country_id' => 'sometimes|required|integer|max:100', 
    //         'order_number' => [
    //             'sometimes',
    //             'required',
    //             'string',
    //             Rule::unique('agent_order_headers', 'order_code')->ignore($orderId)
    //         ],
    //         'warehouse_id' => 'sometimes|required|uuid|exists:warehouses,id',
    //         'route_id' => 'nullable|uuid|exists:routes,id',
    //         'customer_id' => 'sometimes|required|uuid|exists:customers,id',
    //         'salesman_id' => 'sometimes|required|uuid|exists:users,id',
    //         'delivery_date' => 'nullable|date|after_or_equal:today',
    //         'gross_total' => 'nullable|numeric|min:0',
    //         'vat' => 'nullable|numeric|min:0',
    //         'net_amount' => 'nullable|numeric|min:0',
    //         'total' => 'nullable|numeric|min:0',
    //         'discount' => 'nullable|numeric|min:0',
            
    //         // Order details (optional for update)
    //         'details' => 'sometimes|array|min:1',
    //         'details.*.id' => 'nullable|uuid|exists:order_details,id',
    //         'details.*.item_id' => 'required|uuid|exists:items,id',
    //         'details.*.item_price' => 'required|numeric|min:0',
    //         'details.*.quantity' => 'required|numeric|min:0.001',
    //         'details.*.vat' => 'nullable|numeric|min:0',
    //         'details.*.uom' => 'required|string|max:20',
    //         'details.*.discount' => 'nullable|numeric|min:0',
    //         'details.*.discount_id' => 'nullable|uuid|exists:discounts,id',
    //         'details.*.gross_total' => 'nullable|numeric|min:0',
    //         'details.*.net_total' => 'nullable|numeric|min:0',
    //         'details.*.total' => 'nullable|numeric|min:0',
    //         'details.*.is_promotional' => 'nullable|boolean',
    //         'details.*.parent_id' => 'nullable|uuid|exists:order_details,id',
    //         'details.*.promotion_id' => 'nullable|uuid|exists:promotions,id',
    //     ];
    // }

      public function rules(): array
    {
        return [
            'currency' => 'nullable|string|max:10',
            'country_id' => 'nullable|integer|max:100',
            'order_code' => 'nullable|string|unique:agent_order_headers,order_code',
            
            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
            'route_id' => 'nullable|integer|exists:tbl_route,id',
            'customer_id' => 'nullable|integer|exists:agent_customers,id', 
            'salesman_id' => 'nullable|integer|exists:salesman,id',

            'delivery_date' => 'nullable|date|after_or_equal:today',
            'gross_total' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',

            'details' => 'nullable|array|min:1',
            'details.*.item_id' => 'nullable|integer|exists:items,id',
            'details.*.item_price' => 'nullable|numeric|min:0',
            'details.*.quantity' => 'nullable|numeric|min:0.001',
            'details.*.vat' => 'nullable|numeric|min:0',
            'details.*.uom_id' => 'nullable|numeric',
            'details.*.discount' => 'nullable|numeric|min:0',
            'details.*.discount_id' => 'nullable|integer|exists:discounts,id',
            'details.*.gross_total' => 'nullable|numeric|min:0',
            'details.*.net_total' => 'nullable|numeric|min:0',
            'details.*.total' => 'nullable|numeric|min:0',
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
