<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class AgentOrderHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_id' => 'nullable|exists:tbl_agent,id',
            'sap_order_id' => 'nullable|string|max:255',
            'order_number' => 'required|string|max:50|unique:agent_order_headers,order_number,' . $this->id,
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_term' => 'nullable|string|max:20',
            'price_list_id' => 'nullable|exists:tbl_price_list,id',
            'currency' => 'nullable|string|max:10',
            'gross_total' => 'nullable|numeric|min:0',
            'excise' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'pre_vat' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'net_total' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'order_status' => 'nullable|in:DRAFT,PENDING,APPROVED,REJECTED,CANCELLED',
            'reject_reason' => 'nullable|string',
            'order_comment' => 'nullable|string',
            'sales_backoffice_comment' => 'nullable|string|max:500',
            'signature_img' => 'nullable|string|max:500',
            'sap_return_message' => 'nullable|string',
            'is_delivered' => 'boolean',
            'status' => 'boolean',
        ];
    }
}
