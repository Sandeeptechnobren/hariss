<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanReconsileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id'        => 'required|integer',
            'salesman_id'         => 'required|integer',

            'reconsile_date'      => 'required|date',

            'cash_amount'         => 'required|numeric|min:0',
            'credit_amount'       => 'required|numeric|min:0',
            'grand_total_amount'  => 'nullable|numeric|min:0',

            'osa_code'            => 'nullable|string|max:50',

            'items'                       => 'required|array|min:1',
            'items.*.item_id'             => 'required|integer',
            'items.*.load_qty'            => 'nullable|integer|min:0',
            'items.*.unload_qty'          => 'nullable|integer|min:0',
            'items.*.invoice_qty'         => 'nullable|integer|min:0',
        ];
    }
}
