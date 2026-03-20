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
            'status' => 'nullable|integer|in:0,1',
            'applicable_for' => 'required|string|in:Primary,Secondary',

            // 🔹 Multiple IDs (arrays)
            'warehouse_id' => 'nullable|array',
            'warehouse_id.*' => 'integer|exists:tbl_warehouse,id',

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

            'customer_id' => 'nullable|array',
            'customer_id.*' => 'integer|exists:agent_customers,id',

            'customer_category_id' => 'nullable|array',
            'customer_category_id.*' => 'integer|exists:customer_categories,id',

            'customer_type_id' => 'nullable|array',
            'customer_type_id.*' => 'integer|exists:customer_types,id',
            

            'outlet_channel_id' => 'nullable|array',
            'outlet_channel_id.*' => 'integer|exists:outlet_channel,id',

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
     * 🔹 Pre-process input before validation
     * Converts frontend fields to *_id fields and normalizes arrays
     */
    protected function prepareForValidation()
    {
        // Map frontend names → backend *_id fields
        $map = [
            'company' => 'company_id',
            'region' => 'region_id',
            'area' => 'area_id',
            'warehouse' => 'warehouse_id',
            'route' => 'route_id',
            'item' => 'item_id',
            'item_category' => 'item_category_id',
            'customer' => 'customer_id',
            'customer_category' => 'customer_category_id',
            'customer_type' => 'customer_type_id',
            'outlet_channel' => 'outlet_channel_id',
        ];

        $mappedData = [];

        foreach ($map as $frontendKey => $backendKey) {
            if ($this->has($frontendKey)) {
                $value = $this->input($frontendKey);

                // If it's already an array → convert all to int
                if (is_array($value)) {
                    $mappedData[$backendKey] = array_map('intval', $value);
                } else {
                    // If it's a string → split by comma and convert
                    $mappedData[$backendKey] = array_map('intval', explode(',', $value));
                }
            }
        }

        // Merge mapped data into request
        $this->merge($mappedData);
    }
}
