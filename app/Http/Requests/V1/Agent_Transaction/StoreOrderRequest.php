<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'country_id' => 'nullable|integer|max:100',
            'order_code' => 'required|string|unique:agent_order_headers,order_code',

            'warehouse_id' => 'nullable|integer|exists:tbl_warehouse,id',
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
            'status' => 'required|in:0,1',
            'order_flag' => 'nullable|in:1,2,3',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer|exists:items,id',
            'details.*.quantity' => 'nullable|numeric|min:0.001',
            'details.*.uom_id' => 'required|integer|exists:uom,id',
            'details.*.discount' => 'nullable|numeric|min:0',
            'details.*.discount_id' => 'nullable|integer|exists:discounts,id',
            'details.*.gross_total' => 'nullable|numeric|min:0',

            'details.*.isPrmotion' => 'nullable|boolean',
            'details.*.item_price' => 'nullable|numeric|min:0',
            'details.*.vat'        => 'nullable|numeric|min:0',
            'details.*.net_total'  => 'nullable|numeric|min:0',
            'details.*.total'      => 'nullable|numeric|min:0',

            'details.*.parent_id' => 'nullable|integer|exists:agent_order_details,id',
            'details.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $details = $this->input('details', []);

            foreach ($details as $index => $detail) {

                $isPromotional = $detail['isPrmotion'] ?? false;

                if (!$isPromotional) {

                    $requiredFields = [
                        'quantity'   => 'Quantity is required.',
                        'item_price' => 'Item price is required.',
                        'vat'        => 'VAT amount is required.',
                        'net_total'  => 'Net amount is required.',
                        'total'      => 'Total amount is required.',
                    ];

                    foreach ($requiredFields as $field => $message) {
                        if (!isset($detail[$field]) || $detail[$field] === null) {
                            $validator->errors()->add(
                                "details.$index.$field",
                                $message
                            );
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'order_code.unique' => 'This order number already exists.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'delivery_date.required' => 'Delivery date is required.',

            'details.required' => 'Please add at least one item to the order.',
            'details.*.item_id.required' => 'Please select an item.',
            'details.*.item_id.exists' => 'Selected item is invalid.',
            'details.*.uom_id.required' => 'Unit of measurement is required.',
            'details.*.quantity.required' => 'Item quantity is required.',
            'details.*.quantity.min' => 'Item quantity must be greater than zero.',
        ];
    }
}
