<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'  => 'nullable|string|max:15|unique:items,code,' . ($this->route('id') ?? 'NULL') . ',id',
            'erp_code' => 'nullable|string|max:20|unique:items,erp_code,' . ($this->route('id') ?? 'NULL') . ',id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'brand' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:item_categories,id',
            'sub_category_id' => 'nullable|exists:item_sub_categories,id',
            'item_weight' => 'nullable|numeric',
            'shelf_life' => 'nullable|string|max:255',
            'volume' => 'nullable|numeric',
            'is_promotional' => 'nullable|integer|in:0,1',
            'caps_promo' => 'nullable|integer|in:0,1',
            'is_taxable' => 'nullable|integer|in:0,1',
            'has_excies' => 'nullable|integer|in:0,1',
            'status' => 'nullable|integer|in:0,1',
            'commodity_goods_code' => 'nullable|string',
            'excise_duty_code' => 'nullable|string',
            'brand' => 'nullable|integer|exists:tbl_brands,id',
            'discount_status' => 'nullable|bool',

            'uoms' => 'nullable|array|min:1',
            'uoms.*.uom' => 'nullable|string',
            'uoms.*.uom_type' => 'nullable|string|max:50|in:primary,secondary,third,forth',
            'uoms.*.price' => 'nullable|numeric|min:0',
            'uoms.*.upc' => 'nullable|numeric|min:0',
            'uoms.*.is_stock_keeping' => 'nullable|integer|in:0,1',
            'uoms.*.keeping_quantity' => 'nullable|integer',
            'uoms.*.enable_for' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'uoms.required' => 'At least one UOM entry is required.',
            'uoms.*.uom_type.required' => 'Each UOM entry must have a type.',
            'uoms.*.price.required' => 'Each UOM entry must include a price.',
        ];
    }
}
