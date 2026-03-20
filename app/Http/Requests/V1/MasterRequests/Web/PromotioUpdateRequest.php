<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PromotioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'promotion_name'     => 'sometimes|string|max:150',
            'promotion_type'     => 'sometimes|string|max:50',
            'bundle_combination' => 'sometimes|nullable|string|max:50',

            'from_date' => 'sometimes|date',
            'to_date'   => 'sometimes|date|after_or_equal:from_date',

            'status' => 'sometimes|integer|in:0,1',

            'sales_team_type'   => 'sometimes|nullable|array',
            'sales_team_type.*' => 'integer',

            'project_list'   => 'sometimes|nullable|array',
            'project_list.*' => 'integer',

            'uom' => 'sometimes|nullable|integer',

            'items'           => 'sometimes|nullable|array',
            'items.*'         => 'nullable|string',

            'item_category'   => 'sometimes|nullable|array',
            'item_category.*' => 'nullable|string',

            'location'        => 'sometimes|nullable|array',
            'location.*'      => 'nullable|string',

            'customer'        => 'sometimes|nullable|array',
            'customer.*'      => 'nullable|string',

            'key'          => 'sometimes|nullable|array',
            'key.Location' => 'sometimes|nullable|array',
            'key.Customer' => 'sometimes|nullable|array',
            'key.Item'     => 'sometimes|nullable|array',

            'promotion_details'            => 'sometimes|nullable|array',
            'promotion_details.*.id'       => 'sometimes|nullable|integer|exists:tbl_promotion_details,id',
            'promotion_details.*.from_qty' => 'required_with:promotion_details|integer|min:1',
            'promotion_details.*.to_qty'   => 'required_with:promotion_details|integer|gte:promotion_details.*.from_qty',
            'promotion_details.*.free_qty' => 'required_with:promotion_details|integer|min:0',

            'percentage_discounts' => 'sometimes|nullable|array',

            'percentage_discounts.*.percentage_item_id' =>
            'sometimes|nullable|string|max:200',

            'percentage_discounts.*.percentage_item_category' =>
            'nullable:percentage_discounts|string|max:200',

            'percentage_discounts.*.percentage' =>
            'nullable:percentage_discounts|numeric|min:0|max:100',

            'offer_items' => 'sometimes|nullable|array',

            'offer_items.*.item_id' =>
            'required_with:offer_items|string|max:50',

            'offer_items.*.uom' =>
            'required_with:offer_items|string|max:20',
        ];
    }
}
