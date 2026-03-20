<?php

namespace App\Http\Requests\V1\Agent_Transaction\Mob;

use Illuminate\Foundation\Http\FormRequest;

class AgentDeliveryHeaderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_code'  => 'required|string|exists:agent_delivery_headers,delivery_code',
            'warehouse_id'   => 'sometimes|nullable|integer|exists:warehouses,id',
            'customer_id'    => 'sometimes|nullable|integer|exists:customers,id',
            'currency'       => 'sometimes|nullable|string|max:20',
            'country_id'     => 'sometimes|required|integer|exists:countries,id',
            'route_id'       => 'sometimes|required|integer|exists:routes,id',
            'salesman_id'    => 'sometimes|required|integer|exists:salesmen,id',
            'gross_total'    => 'sometimes|nullable|numeric|min:0',
            'vat'            => 'sometimes|nullable|numeric|min:0',
            'discount'       => 'sometimes|nullable|numeric|min:0',
            'net_amount'     => 'sometimes|nullable|numeric|min:0',
            'total'          => 'sometimes|nullable|numeric|min:0',
            'delivery_date'  => 'sometimes|nullable|date',
            'comment'        => 'sometimes|nullable|string|max:500',
            'status'         => 'sometimes|nullable|integer|in:0,1,2',

            // ?? Details array
            'details'                             => 'sometimes|required|array|min:1',
            'details.*.item_id'                   => 'sometimes|required|integer|exists:items,id',
            'details.*.uom_id'                    => 'sometimes|nullable|integer',
            'details.*.discount_id'               => 'sometimes|nullable|integer',
            'details.*.promotion_id'              => 'sometimes|nullable|integer',
            'details.*.parent_id'                 => 'sometimes|nullable|integer',
            'details.*.item_price'                => 'sometimes|nullable|numeric|min:0',
            'details.*.quantity'                  => 'sometimes|nullable|numeric|min:0',
            'details.*.vat'                       => 'sometimes|nullable|numeric|min:0',
            'details.*.discount'                  => 'sometimes|nullable|numeric|min:0',
            'details.*.gross_total'               => 'sometimes|nullable|numeric|min:0',
            'details.*.net_total'                 => 'sometimes|nullable|numeric|min:0',
            'details.*.total'                     => 'sometimes|nullable|numeric|min:0',
            'details.*.is_promotional'            => 'sometimes|nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'details.required' => 'At least one delivery detail is required.',
            'details.*.item_id.required' => 'Item ID is required for each detail line.',
            'details.*.uom_id.required' => 'UOM is required for each detail line.',
        ];
    }
}
