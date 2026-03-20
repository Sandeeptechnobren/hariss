<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class ViewStockPostRequest extends FormRequest
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
            'capacity'          => 'nullable|numeric|min:0',
            'good_salable'      => 'nullable|numeric|min:0',
            'out_of_stock' 	=> 'nullable|boolean',
            'shelf_id'          => 'required|integer|exists:shelves,id',
        ];
    }
}