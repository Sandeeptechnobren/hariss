<?php

// namespace App\Http\Requests\V1\MasterRequests\Web;

// use Illuminate\Foundation\Http\FormRequest;

// class ItemRequest extends FormRequest
// {
//     public function authorize(): bool
//     {
//         return true;
//     }

//     public function rules(): array
//     {
//         return [
//             'code'  => 'required|string|max:15|unique:items,code,' . ($this->route('id') ?? 'NULL') . ',id',
//             'erp_code' => 'nullable|string|max:20|unique:items,erp_code,' . ($this->route('id') ?? 'NULL') . ',id',
//             'name' => 'required|string|max:255',
//             'description' => 'required|string|max:255',
//             'image' => 'required|integer|min:1',
//             'brand'=> 'required|string|max:50',
//             'category_id' => 'required|exists:item_categories,id',
//             'sub_category_id' => 'required|exists:item_sub_categories,id',
//             'item_weight' => 'required|numeric',
//             'shelf_life' => 'required|numeric',
//             'volume' => 'required|numeric',
//             'is_promotional' => 'required|integer|in:0,1',
//             'is_taxable' => 'required|integer|in:0,1',
//             'has_excies' => 'required|integer|in:0,1',
//             'status' => 'required|integer|in:0,1',
//             'commodity_goods_code'=>'required|string',
//             'excise_duty_code'=>'required|string',

//             'uoms' => 'required|array|min:1', 
//             'uoms.*.uom'=>'required|string',
//             'uoms.*.uom_type' => 'required|string|max:50|in:primary,secondary,third,forth',
//             'uoms.*.price' => 'required|numeric|min:0',
//             'uoms.*.upc'=>'nullable|numeric|min:0',
//             'uoms.*.is_stock_keeping' => 'required|integer|in:0,1',
//             'uoms.*.keeping_quantity' => 'nullable|integer',
//             'uoms.*.enable_for' => 'nullable|string|max:50',
//         ];
//     }

//     public function messages(): array
//     {
//         return [
//             'uoms.required' => 'At least one UOM entry is required.',
//             'uoms.*.uom_type.required' => 'Each UOM entry must have a type.',
//             'uoms.*.price.required' => 'Each UOM entry must include a price.',
//         ];
//     }
// }

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
            'code'  => 'required|string|max:15|unique:items,code,' . ($this->route('id') ?? 'NULL') . ',id',
            'erp_code' => 'nullable|string|max:20|unique:items,erp_code,' . ($this->route('id') ?? 'NULL') . ',id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
<<<<<<< HEAD
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
=======
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
>>>>>>> ae60f28181c47afb1219a37ca58155c941012dd6
            'brand'=> 'required|string|max:50',
            'category_id' => 'required|exists:item_categories,id',
            'sub_category_id' => 'required|exists:item_sub_categories,id',
            'item_weight' => 'required|numeric',
            'shelf_life' => 'required|numeric',
            'volume' => 'required|numeric',
            'is_promotional' => 'required|integer|in:0,1',
            'is_taxable' => 'required|integer|in:0,1',
            'has_excies' => 'required|integer|in:0,1',
            'status' => 'required|integer|in:0,1',
            'commodity_goods_code'=>'required|string',
            'excise_duty_code'=>'required|string',

            'uoms' => 'nullable|array|min:1', 
            'uoms.*.uom'=>'nullable|string',
            'uoms.*.uom_type' => 'nullable|string|max:50|in:primary,secondary,third,forth',
            'uoms.*.price' => 'nullable|numeric|min:0',
            'uoms.*.upc'=>'nullable|numeric|min:0',
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