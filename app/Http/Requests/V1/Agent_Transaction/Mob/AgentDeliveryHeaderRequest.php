<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

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
            'delivery_code'=> 'required|string|unique:agent_delivery_headers,delivery_code',
            'warehouse_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'currency' => 'nullable|string|max:20',
            'country_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
            'salesman_id' => 'nullable|integer',
            'gross_total' => 'nullable|numeric',
            'vat' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'net_amount' => 'nullable|numeric',
            'total' => 'required|numeric',
            'order_code' => 'nullable|string|unique:agent_delivery_headers,order_code|exists:agent_order_headers',
            'comment' => 'nullable|string',
            'status' => 'nullable|integer',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|integer',
            'details.*.uom_id' => 'required|integer',
            'details.*.item_price' => 'required|numeric',
            'details.*.quantity' => 'required|integer',
            'details.*.vat' => 'required|numeric',
            'details.*.discount' => 'nullable|numeric',
            'details.*.gross_total' => 'nullable|numeric',
            'details.*.net_total' => 'nullable|numeric',
            'details.*.total' => 'required|numeric',
            'details.*.is_promotional' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'order_code.unique' => 'Delivery has already been created for the following order.',
            'order_code.exists' => 'The selected Order code is invalid.'
        ];
    }
}
