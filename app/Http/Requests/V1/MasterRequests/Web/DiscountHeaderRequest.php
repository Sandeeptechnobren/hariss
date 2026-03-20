<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class DiscountHeaderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'discount_name'      => 'required|string|max:150',
            'discount_apply_on'  => 'required|string|max:150',
            'discount_type'      => 'required|string|max:150',
            'bundle_combination' => 'nullable|string|max:150',

            'from_date'          => 'required|date',
            'to_date'            => 'required|date|after_or_equal:from_date',
            'status'             => 'nullable|integer|in:0,1',
            'header'                         => 'nullable|array',
            'header.headerMinAmount'         => 'nullable|numeric',
            'header.headerRate'              => 'nullable|numeric',

            'sales_team_type'    => 'nullable|array',
            'sales_team_type.*'  => 'string',

            'project_list'       => 'nullable|array',
            'project_list.*'     => 'string',

            'items'              => 'nullable|array',
            'items.*'            => 'string',

            'item_category'      => 'nullable|array',
            'item_category.*'    => 'string',

            'location'           => 'nullable|array',
            'location.*'         => 'string',

            'customer'           => 'nullable|array',
            'customer.*'         => 'string',

            'uom'                => 'nullable|string',

            'discount_details'               => 'nullable|array',

            'discount_details.*.item_id'     => 'nullable|string',
            'discount_details.*.category_id' => 'nullable|string',
            'discount_details.*.uom'         => 'nullable|string',

            'discount_details.*.percentage'  => 'nullable|numeric',
            'discount_details.*.amount'      => 'nullable|numeric',

            'key'                    => 'nullable|array',
            'key.Location'           => 'nullable|array',
            'key.Customer'           => 'nullable|array',
            'key.Item'               => 'nullable|array',

            'key.Location.*'         => 'string',
            'key.Customer.*'         => 'string',
            'key.Item.*'             => 'string',
        ];
    }

    /**
     * Custom validation logic
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->discount_details ?? [] as $index => $detail) {

                // Either percentage or amount must be present
                if (
                    empty($detail['percentage']) &&
                    empty($detail['amount'])
                ) {
                    $validator->errors()->add(
                        "discount_details.$index",
                        'Either percentage or amount is required.'
                    );
                }

                // At least item_id or category_id must exist
                if (
                    empty($detail['item_id']) &&
                    empty($detail['category_id'])
                ) {
                    $validator->errors()->add(
                        "discount_details.$index",
                        'Either item_id or category_id is required.'
                    );
                }
            }
        });
    }
}
