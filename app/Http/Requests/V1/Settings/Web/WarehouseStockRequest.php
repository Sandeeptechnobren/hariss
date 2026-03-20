<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\WarehouseStock;

class WarehouseStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'nullable|string',
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'item_id' => 'required|integer|exists:items,id',
            'qty' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * After basic validation, check unique (warehouse_id + item_id) combination.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $exists = WarehouseStock::where('warehouse_id', $this->warehouse_id)
                ->where('item_id', $this->item_id)
                ->whereNull('deleted_at') // ignore soft-deleted records
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'item_id',
                    'The item already exists, please update your item stock.'
                );
            }
        });
    }
}
