<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id'     => 'required|integer|exists:invoice_headers,id',
            'customer_id'    => 'required|integer|exists:agent_customers,id',
            'warehouse_id'   => 'required|integer|exists:tbl_warehouse,id',
            'route_id'       => 'required|integer|exists:tbl_route,id',
            'salesman_id'    => 'required|integer|exists:salesman,id',
            'amount'         => 'required|numeric|min:0',
            'outstanding'    => 'nullable|numeric|min:0',
            'status'         => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required'    => 'The invoice field is required.',
            'invoice_id.exists'      => 'The selected invoice does not exist.',
            'customer_id.required'   => 'The customer field is required.',
            'customer_id.exists'     => 'The selected customer does not exist.',
            'collection_no.unique'   => 'This collection number has already been taken.',
        ];
    }
}
