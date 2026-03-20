<?php
namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
            'item_id' => 'required|integer|exists:items,id',
            'qty' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|in:0,1',
        ];
    }
}
