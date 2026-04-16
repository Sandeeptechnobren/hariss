<?php

namespace App\Http\Requests\V1\Agent_Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAuditRequest extends FormRequest
{
    public function rules()
    {
        return [
            'osa_code' => 'required|string',
            'warehouse_id' => 'required|integer',
            'auditer_name' => 'required|string',

            'case_otc_invoice' => 'nullable|integer',
            'otc_invoice'      => 'nullable|integer',
            'negative_balance_date' => 'nullable|integer',

            'items' => 'required|array|min:1',

            'items.*.item_id'         => 'required|integer',
            'items.*.uom_id'          => 'nullable|integer',
            'items.*.warehouse_stock' => 'nullable|numeric',
            'items.*.physical_stock'  => 'nullable|numeric',
            'items.*.variance'        => 'nullable|numeric',
            'items.*.saleon_otc'      => 'nullable',
            'items.*.remarks'         => 'nullable|string',
        ];
    }
}
