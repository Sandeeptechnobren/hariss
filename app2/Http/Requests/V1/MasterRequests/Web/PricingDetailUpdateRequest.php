<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PricingDetailUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 🔹 Header fields
            'name' => 'sometimes|string|max:55',
            'description' => 'sometimes|array',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'apply_on' => 'sometimes|integer|in:0,1',
            'status' => 'sometimes|integer|in:0,1',

            // 🔹 Multiple IDs (arrays)
            'warehouse_id' => 'sometimes|array',
            'warehouse_id.*' => 'integer|exists:tbl_warehouse,id',

            'item_type' => 'sometimes|array',
            'item_type.*' => 'integer',

            'company_id' => 'sometimes|array',
            'company_id.*' => 'integer|exists:tbl_company,id',

            'region_id' => 'sometimes|array',
            'region_id.*' => 'integer|exists:tbl_region,id',

            'area_id' => 'sometimes|array',
            'area_id.*' => 'integer|exists:tbl_areas,id',

            'route_id' => 'sometimes|array',
            'route_id.*' => 'integer|exists:tbl_route,id',

            'item_id' => 'sometimes|array',
            'item_id.*' => 'integer|exists:items,id',

            'item_category_id' => 'sometimes|array',
            'item_category_id.*' => 'integer|exists:item_categories,id',

            'customer_category_id' => 'sometimes|array',
            'customer_category_id.*' => 'integer|exists:customer_categories,id',

            'customer_type_id' => 'sometimes|array',
            'customer_type_id.*' => 'integer|exists:customer_types,id',

            'outlet_channel_id' => 'sometimes|array',
            'outlet_channel_id.*' => 'integer|exists:outlet_channel,id',

            'customer_id' => 'sometimes|array',
            'customer_id.*' => 'integer|exists:agent_customers,id',

            // 🔹 Details array
            'details' => 'sometimes|array|min:1',
            'details.*.name' => 'sometimes|string|max:150',
            'details.*.item_id' => 'sometimes|integer|exists:items,id',
            'details.*.buom_ctn_price' => 'sometimes|numeric|min:0',
            'details.*.auom_pc_price' => 'sometimes|numeric|min:0',
            'details.*.status' => 'sometimes|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'details.*.item_id.exists' => 'The selected item does not exist in the items table.',
            'details.required' => 'At least one pricing detail record is required.',
        ];
    }

    /**
     * 🔹 Automatically convert comma-separated strings → arrays
     */
    protected function prepareForValidation()
    {
        $multiFields = [
            'warehouse_id',
            'item_type',
            'company_id',
            'region_id',
            'area_id',
            'route_id',
            'sometimes',
            'item_id',
            'item_category_id',
            'customer_category_id',
            'customer_id',
            'customer_type_id',
            'customer_id',
            'outlet_channel_id'
        ];

        foreach ($multiFields as $field) {
            if ($this->filled($field) && is_string($this->$field)) {
                $this->merge([
                    $field => array_map('intval', explode(',', $this->$field))
                ]);
            }
        }
    }
}
