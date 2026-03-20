<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PricingHeaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'sometimes|string|max:255|unique:pricing_headers,code,' . ($this->route('id') ?? 'NULL') . ',id',
            'description' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'apply_on' => 'nullable|integer|in:0,1',
            'warehouse_id' => 'nullable|exists:tbl_warehouse,id',
            'item_type' => 'nullable|exists:item_categories,id',
            'status' => 'required|in:0,1'
        ];
    }
}
