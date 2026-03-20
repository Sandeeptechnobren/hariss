<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

 public function rules(): array
{
    return [
        'exchange_code' => 'nullable|string|unique:exchange_headers,exchange_code',
        'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
        'route_id' => 'nullable|integer|exists:tbl_route,id',
        'customer_id' => 'required|integer|exists:agent_customers,id',
        'comment'     => 'nullable|string',
        'status' => 'nullable|integer|min:0',

        'invoices' => 'required|array|min:1',
        'invoices.*.item_id' => 'required|integer|exists:items,id',
        'invoices.*.uom_id' => 'required|integer|exists:uom,id',
        'invoices.*.item_price' => 'required|numeric|min:0',
        'invoices.*.item_quantity' => 'required|numeric|min:0.001',
        'invoices.*.total' => 'required|numeric|min:0',
        'invoices.*.status' => 'nullable|integer|min:0',

        'returns' => 'required|array|min:1',
        'returns.*.item_id' => 'required|integer|exists:items,id',
        'returns.*.uom_id' => 'required|integer|exists:uom,id',
        'returns.*.item_price' => 'required|numeric|min:0',
        'returns.*.item_quantity' => 'required|numeric|min:0.001',
        'returns.*.total' => 'required|numeric|min:0',
        'returns.*.status' => 'nullable|integer|min:0',
        'returns.*.return_type' => 'required|string',
        'returns.*.region' => 'required|string',
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
