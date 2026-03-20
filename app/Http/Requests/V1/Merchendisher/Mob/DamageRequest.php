<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class DamageRequest extends FormRequest
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
            'damage_qty'        => 'nullable|integer|min:0',
            'expiry_qty'        => 'nullable|integer|min:0',
            'salable_qty'       => 'nullable|integer|min:0',
            'shelf_id'          => 'required|integer|exists:shelves,id',
        ];
    }
}