<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockInStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|unique:stock_in_store,code',
            'activity_name' => 'required|string|max:255',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'assign_customers' => 'required|array',
            'assign_customers.*' => 'integer',
            'assign_inventory' => 'required|array|min:1',
            'assign_inventory.*.item_id'   => 'required|exists:items,id',
            'assign_inventory.*.item_uom'  => 'required|string|max:50',
            'assign_inventory.*.capacity'  => 'required|integer|min:1',
        ];
    }
}