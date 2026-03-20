<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PricingDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 🔹 Header fields
            'name' => 'required|string|max:55',
            'description' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'apply_on' => 'nullable|integer|in:0,1',
            'warehouse_id' => 'nullable|array',
            'warehouse_id.*' => 'integer|exists:tbl_warehouse,id',
            'status' => 'nullable|integer|in:0,1',

            // 🔹 Multiple IDs (arrays)
            'company_id' => 'nullable|array',
            'company_id.*' => 'integer|exists:tbl_company,id',

            'region_id' => 'nullable|array',
            'region_id.*' => 'integer|exists:tbl_region,id',

            'area_id' => 'nullable|array',
            'area_id.*' => 'integer|exists:tbl_areas,id',

            'route_id' => 'nullable|array',
            'route_id.*' => 'integer|exists:tbl_route,id',

            'item_id' => 'nullable|array',
            'item_id.*' => 'integer|exists:items,id',

            'item_category_id' => 'nullable|array',
            'item_category_id.*' => 'integer|exists:item_categories,id',

            'customer_category_id' => 'nullable|array',
            'customer_category_id.*' => 'integer|exists:customer_categories,id',

            'customer_type_id' => 'nullable|array',
            'customer_type_id.*' => 'integer|exists:customer_types,id',

            'outlet_channel_id' => 'nullable|array',
            'outlet_channel_id.*' => 'integer|exists:outlet_channel,id',

            'customer_id' => 'nullable|array',
            'customer_id.*' => 'integer|exists:agent_customers,id',

            // 🔹 Details array
            'details' => 'nullable|array|min:1',
            'details.*.name' => 'nullable|string|max:150',
            'details.*.item_id' => 'nullable|integer|exists:items,id',
            'details.*.buom_ctn_price' => 'nullable|numeric|min:0',
            'details.*.auom_pc_price' => 'nullable|numeric|min:0',
            'details.*.status' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'The :attribute field must contain numeric IDs separated by commas only (e.g., "1,2,3").',
            'details.required' => 'At least one pricing detail record is required.',
            'details.*.item_id.exists' => 'The selected item does not exist in the items table.',
        ];
    }

    /**
     * 🔹 Optional: Automatically convert "1,2,3" → [1,2,3] before controller
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
            'item_id',
            'item_category_id',
            'customer_id',
            'customer_category_id',
            'customer_type_id',
            'outlet_channel_id'
        ];

        foreach ($multiFields as $field) {
            if ($this->filled($field)) {
                $this->merge([
                    $field => array_map('intval', explode(',', $this->$field))
                ]);
            }
        }
    }
}
