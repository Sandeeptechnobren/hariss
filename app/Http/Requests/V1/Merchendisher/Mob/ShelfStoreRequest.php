<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class ShelfStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // DAMAGE
            'damage' => 'nullable|array',
            'damage.*.date'             => 'required_with:damage|date',
            'damage.*.merchandisher_id' => 'required_with:damage|integer|exists:salesman,id',
            'damage.*.customer_id'      => 'required_with:damage|integer|exists:tbl_company_customer,id',
            'damage.*.item_id'          => 'required_with:damage|integer|exists:items,id',
            'damage.*.damage_qty'       => 'nullable|integer|min:0',
            'damage.*.expiry_qty'       => 'nullable|integer|min:0',
            'damage.*.salable_qty'      => 'nullable|integer|min:0',
            'damage.*.shelf_id'         => 'required_with:damage|integer|exists:shelves,id',

            // EXPIRY
            'expiry' => 'nullable|array',
            'expiry.*.date'             => 'required_with:expiry|date',
            'expiry.*.merchandisher_id' => 'required_with:expiry|integer|exists:salesman,id',
            'expiry.*.customer_id'      => 'required_with:expiry|integer|exists:tbl_company_customer,id',
            'expiry.*.item_id'          => 'required_with:expiry|integer|exists:items,id',
            'expiry.*.qty'              => 'nullable|numeric|min:0',
            'expiry.*.expiry_date'      => 'nullable|date',
            'expiry.*.shelf_id'         => 'required_with:expiry|integer|exists:shelves,id',

            // VIEW STOCK
            'view_stock' => 'nullable|array',
            'view_stock.*.date'             => 'required_with:view_stock|date',
            'view_stock.*.merchandisher_id' => 'required_with:view_stock|integer|exists:salesman,id',
            'view_stock.*.customer_id'      => 'required_with:view_stock|integer|exists:tbl_company_customer,id',
            'view_stock.*.item_id'          => 'required_with:view_stock|integer|exists:items,id',
            'view_stock.*.capacity'         => 'nullable|numeric|min:0',
            'view_stock.*.good_salable'     => 'nullable|numeric|min:0',
            'view_stock.*.out_of_stock'     => 'nullable|boolean',
            'view_stock.*.shelf_id'         => 'required_with:view_stock|integer|exists:shelves,id',
        ];
    }
}