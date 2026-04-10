<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class AgentDeliveryHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_code' => 'required|string|unique:agent_delivery_headers,delivery_code',
            'warehouse_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'currency' => 'nullable|string|max:20',
            'country_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
            'salesman_id' => 'nullable|integer',
            'delivery_date' => 'nullable|date',
            'gross_total' => 'nullable|numeric',
            'vat' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'net_amount' => 'nullable|numeric',
            'total' => 'required|numeric',
            'order_code' => 'required|string|unique:agent_delivery_headers,order_code|exists:agent_order_headers',
            'comment' => 'nullable|string',
            'status' => 'nullable|integer',

            'details'              => 'required|array|min:1',
            'details.*.item_id'    => 'required|integer',
            'details.*.uom_id'     => 'required|integer',
            'details.*.item_price' => 'nullable|numeric|min:0',
            'details.*.quantity'   => 'nullable|numeric|min:0',
            'details.*.vat'        => 'nullable|numeric|min:0',
            'details.*.gross_total' => 'nullable|numeric|min:0',
            'details.*.net_total'  => 'nullable|numeric|min:0',
            'details.*.total'      => 'nullable|numeric|min:0',
            'details.*.promotion_id' => 'nullable|integer',
            'details.*.is_promotional' => 'nullable|boolean',
        ];
    }
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            foreach ($this->input('details', []) as $index => $detail) {

                $isPromotion = $detail['is_promotional'] ?? false;

                if (!$isPromotion) {

                    $requiredFields = [
                        'item_price' => 'Item price is required.',
                        'quantity'   => 'Quantity is required.',
                        'vat'        => 'VAT is required.',
                        'net_total'  => 'Net total is required.',
                        'total'      => 'Total is required.',
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
            'order_code.unique' => 'Delivery has already been created for the following order.',
            'order_code.exists' => 'The selected Order code is invalid.'
        ];
    }
}
