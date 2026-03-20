<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'currency' => 'nullable|string|max:10',
            'country_id' => 'nullable|integer|exists:tbl_country,id',
            'order_number' => 'nullable|integer|exists:agent_order_headers,id',
            'delivery_number' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
            'route_id' => 'nullable|integer|exists:tbl_route,id',
            'customer_id' => 'nullable|integer|exists:agent_customers,id',
            'salesman_id' => 'nullable|integer|exists:salesman,id',
            'delivery_date' => 'nullable|date|after_or_equal:today',
            'gross_total' => 'nullable|numeric|min:0',
            'VAT' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',

            'details' => 'nullable|array|min:1',
            'details.*.item_id' => 'nullable|integer|exists:items,id',
            'details.*.uom_id' => 'nullable|integer|exists:item_uoms,id',
            'details.*.item_price' => 'nullable|numeric|min:0',
            'details.*.item_quantity' => 'nullable|numeric|min:0.001',
            'details.*.VAT' => 'nullable|numeric|min:0',
            'details.*.discounts' => 'nullable|numeric|min:0',
            'details.*.gross_total' => 'nullable|numeric|min:0',
            'details.*.net_total' => 'nullable|numeric|min:0',
            'details.*.total' => 'nullable|numeric|min:0',
            'details.*.is_promotional' => 'nullable|boolean',
            'details.*.discount_id' => 'nullable|integer|exists:discounts,id',
            'details.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
            'details.*.parent_id' => 'nullable|integer|exists:invoice_details,id',
        ];
    }

    public function messages(): array
    {
        return [
            'details.required' => 'Invoice must have at least one item.',
            'details.*.item_id.exists' => 'One or more items do not exist.',
        ];
    }
}