<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class AsignInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'     => 'required|exists:items,id',   // item from items table
            'customer_id' => 'required|exists:stock_in_store,id',
            'capacity'    => 'required|integer|min:1',
        ];
    }
}
