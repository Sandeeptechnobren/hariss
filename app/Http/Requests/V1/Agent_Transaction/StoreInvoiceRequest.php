<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
         return [
            'invoice_code' => 'required|string|unique:invoice_headers,invoice_code',
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'order_id' => 'nullable|integer|exists:agent_order_headers,id',
            'delivery_id' => 'nullable|integer|exists:agent_delivery_headers,id',
            'customer_id' => 'required|integer|exists:agent_customers,id',
            'route_id' => 'nullable|integer|exists:tbl_route,id',
            'salesman_id' => 'nullable|integer|exists:salesman,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'invoice_date' => 'required|date',
            'invoice_time' => 'required|date_format:H:i:s',
            'gross_total' => 'required|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'pre_vat' => 'nullable|numeric|min:0',
            'net_total' => 'required|numeric|min:0',
            'promotion_id' => 'nullable|integer|exists:promotion_headers,id',
            'discount_id' => 'nullable|integer',
            'discount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'promotion_total' => 'nullable|numeric|min:0',
            'purchaser_name' => 'nullable|string|max:255',
            'purchaser_contact' => 'nullable|string|max:100',
            'status'=> 'required|in:0,1',
            'invoice_type' => 'nullable|in:1,0',
            'details' => 'nullable|array|min:1',
            'details.*.item_id' => 'nullable|integer|exists:items,id',
            'details.*.uom' => 'nullable|integer|exists:uom,id',
            'details.*.quantity' => 'nullable|numeric|min:0.001',
            'details.*.item_price' => 'nullable|numeric|min:0',
            'details.*.vat' => 'nullable|numeric|min:0',
            'details.*.pre_vat' => 'nullable|numeric|min:0',
            'details.*.net_total' => 'nullable|numeric|min:0',
            'details.*.item_total' => 'nullable|numeric|min:0',
            'details.*.promotion_id' => 'nullable|integer|exists:promotion_headers,id',
            'details.*.parent' => 'nullable|integer|exists:invoice_details,id',
            'details.*.approver_id' => 'nullable|integer|exists:approvers,id',
            'details.*.approved_date' => 'nullable|date',
            'details.*.rejected_by' => 'nullable|integer|exists:approvers,id',
            'details.*.rm_approver_id' => 'nullable|integer|exists:approvers,id',
            'details.*.rm_reject_id' => 'nullable|integer|exists:approvers,id',
            'details.*.rmaction_date' => 'nullable|date',
            'details.*.comment_for_rejection' => 'nullable|string|max:500',
            'details.*.status' => 'nullable|integer|min:0|max:1',
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