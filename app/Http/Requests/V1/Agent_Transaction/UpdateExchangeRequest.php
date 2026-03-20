<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

 public function rules(): array
{
    return [
        'currency' => 'nullable|string|max:55',
        'country_id' => 'nullable|integer|exists:tbl_country,id',
        'order_id' => 'nullable|integer|exists:agent_order_headers,id',
        'delivery_id' => 'nullable|integer',
        'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
        'route_id' => 'nullable|integer|exists:tbl_route,id',
        'customer_id' => 'nullable|integer|exists:agent_customers,id',
        'salesman_id' => 'nullable|integer|exists:salesman,id',
        'gross_total' => 'nullable|numeric|min:0',
        'vat' => 'nullable|numeric|min:0',
        'net_amount' => 'nullable|numeric|min:0',
        'total' => 'nullable|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'status' => 'nullable|integer|min:0',

        // Invoices
        'invoices' => 'nullable|array|min:1',
        'invoices.*.item_id' => 'nullable|integer|exists:items,id',
        'invoices.*.uom_id' => 'nullable|integer|exists:item_uoms,id',
        'invoices.*.item_price' => 'nullable|numeric|min:0',
        'invoices.*.item_quantity' => 'nullable|numeric|min:0.001',
        'invoices.*.vat' => 'nullable|numeric|min:0',
        'invoices.*.discount' => 'nullable|numeric|min:0',
        'invoices.*.gross_total' => 'nullable|numeric|min:0',
        'invoices.*.net_total' => 'nullable|numeric|min:0',
        'invoices.*.total' => 'nullable|numeric|min:0',
        'invoices.*.is_promotional' => 'nullable|boolean',
        'invoices.*.discount_id' => 'nullable|integer|exists:discounts,id',
        'invoices.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
        'invoices.*.parent_id' => 'nullable|integer',
        'invoices.*.status' => 'nullable|integer|min:0',

        // Returns
        'returns' => 'nullable|array|min:1',
        'returns.*.item_id' => 'nullable|integer|exists:items,id',
        'returns.*.uom_id' => 'nullable|integer|exists:item_uoms,id',
        'returns.*.item_price' => 'nullable|numeric|min:0',
        'returns.*.item_quantity' => 'nullable|numeric|min:0.001',
        'returns.*.vat' => 'nullable|numeric|min:0',
        'returns.*.discount' => 'nullable|numeric|min:0',
        'returns.*.gross_total' => 'nullable|numeric|min:0',
        'returns.*.net_total' => 'nullable|numeric|min:0',
        'returns.*.total' => 'nullable|numeric|min:0',
        'returns.*.is_promotional' => 'nullable|boolean',
        'returns.*.discount_id' => 'nullable|integer|exists:discounts,id',
        'returns.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
        'returns.*.parent_id' => 'nullable|integer',
        'returns.*.status' => 'nullable|integer|min:0',
    ];
}


    public function messages(): array
    {
        return [
            'details.required' => 'An exchange must have at least one item.',
            'details.*.item_id.exists' => 'One or more items in the exchange do not exist.',
            'details.*.uom_id.exists' => 'One or more UOMs in the exchange are invalid.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'route_id.exists' => 'The selected route does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'salesman_id.exists' => 'The selected salesman does not exist.',
        ];
    }
}
