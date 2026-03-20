<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PromotionHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'nullable|string|max:20|unique:tbl_promotion_headers,osa_code',

            'promotion_name'     => 'required|string|max:150',
            'promotion_type'     => 'required|string|max:50',
            'bundle_combination' => 'nullable|string|max:50',

            'from_date' => 'required|date',
            'to_date'   => 'required|date|after_or_equal:from_date',

            'status' => 'required|integer|in:0,1',

            'sales_team_type'   => 'nullable|array',
            'sales_team_type.*' => 'integer',

            'project_list'   => 'nullable|array',
            'project_list.*' => 'integer',

            'uom' => 'nullable|integer',

            'items'           => 'nullable|array',
            'items.*'         => 'nullable|string',

            'item_category'   => 'nullable|array',
            'item_category.*' => 'nullable|string',

            'location'        => 'nullable|array',
            'location.*'      => 'nullable|string',

            'customer'        => 'nullable|array',
            'customer.*'      => 'nullable|string',

            'key'             => 'nullable|array',
            'key.Location'    => 'nullable|array',
            'key.Customer'    => 'nullable|array',
            'key.Item'        => 'nullable|array',

            'promotion_details'            => 'required|array',
            'promotion_details.*.from_qty' => 'required|integer|min:1',
            'promotion_details.*.to_qty'   => 'required|integer|gte:promotion_details.*.from_qty',
            'promotion_details.*.free_qty' => 'required|integer|min:0',

            'percentage_discounts' => 'nullable|array',

            'percentage_discounts.*.percentage_item_id' =>
            'nullable|string|max:200',

            'percentage_discounts.*.percentage_item_category' =>
            'nullable:percentage_discounts|string|max:200',

            'percentage_discounts.*.percentage' =>
            'nullable:percentage_discounts|numeric|min:0|max:100',

            'offer_items' => 'nullable|array',

            'offer_items.*.item_id' =>
            'required_with:offer_items|string|max:50',

            'offer_items.*.uom' =>
            'required_with:offer_items|string|max:20',
        ];
    }
}
