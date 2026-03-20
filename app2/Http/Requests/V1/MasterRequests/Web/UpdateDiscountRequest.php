<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'            => 'sometimes|integer|exists:items,id',
            'category_id'        => 'sometimes|integer|exists:item_categories,id',
            'customer_id'        => 'sometimes|integer',
            'customer_channel_id'=> 'sometimes|integer',
            'discount_type'  => 'sometimes|exists:discount_types,id',
            'discount_value' => 'sometimes|numeric|min:0',
            'min_quantity'   => 'sometimes|integer|min:0',
            'min_order_value'=> 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:0,1',
        ];
    }
}
