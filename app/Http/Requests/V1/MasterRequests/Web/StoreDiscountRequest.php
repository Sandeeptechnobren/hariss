<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code'           => 'nullable|integer|unique:discounts',
            'item_id'            => 'required|integer|exists:items,id',
            'category_id'        => 'required|integer|exists:item_categories,id',
            'customer_id'        => 'required|integer',
            'customer_channel_id' => 'required|integer',
            'discount_type'  => 'required|exists:discount_types,id',
            'discount_value' => 'required|numeric|min:0',
            'min_quantity'   => 'required|integer|min:0',
            'min_order_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:0,1',
        ];
    }
}
