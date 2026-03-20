<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class ExpiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'              => 'required|date',
            'merchandisher_id'  => 'required|integer|exists:salesman,id',
            'customer_id'       => 'required|integer|exists:tbl_company_customer,id',
            'item_id'           => 'required|integer|exists:items,id',
            'qty'               => 'nullable|numeric|min:0',
            'expiry_date'       => 'nullable|date',
            'shelf_id'          => 'required|integer|exists:shelves,id',
        ];
    }
}