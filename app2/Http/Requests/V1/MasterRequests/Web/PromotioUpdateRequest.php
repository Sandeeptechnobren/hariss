<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PromotioUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'key_combination' => 'nullable|string|max:50',
            'promotion_name' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'warehouse_ids' => 'nullable|integer',
            'manager_ids' => 'nullable|integer',
            'projects_id' => 'nullable|integer',
            'included_customer_id' => 'nullable|integer',
            'excluded_customer_ids' => 'nullable|integer',
            'assignment_uom' => 'nullable|integer',
            'qualification_uom' => 'nullable|integer',
            'outlet_channel_id' => 'nullable|string|max:200',
            'customer_category_id' => 'nullable|exists:customer_categories,id',
            'bought_item_ids' => 'nullable|string',
            'bonus_item_ids' => 'nullable|string',
            'status' => 'nullable|in:1,0',

            'promotion_details' => 'sometimes|array',
            'promotion_details.*.lower_qty' => 'sometimes|integer|min:0', 
            'promotion_details.*.upper_qty' => 'sometimes|integer|min:0',  
            'promotion_details.*.free_qty' => 'sometimes|integer|min:0',  
        ];
    }

    public function messages()
    {
        return [
            'promotion_details.required' => 'The promotion details field is required.',
            'promotion_details.array' => 'The promotion details must be an array.',
            'promotion_details.*.lower_qty.required' => 'The lower quantity is required for each promotion detail.',
            'promotion_details.*.upper_qty.required' => 'The upper quantity is required for each promotion detail.',
            'promotion_details.*.free_qty.required' => 'The free quantity is required for each promotion detail.',
            'promotion_details.*.lower_qty.integer' => 'The lower quantity must be an integer.',
            'promotion_details.*.upper_qty.integer' => 'The upper quantity must be an integer.',
            'promotion_details.*.free_qty.integer' => 'The free quantity must be an integer.',
            'promotion_details.*.lower_qty.min' => 'The lower quantity must be at least 0.',
            'promotion_details.*.upper_qty.min' => 'The upper quantity must be at least 0.',
            'promotion_details.*.free_qty.min' => 'The free quantity must be at least 0.',
        ];
    }
}
