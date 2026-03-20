<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class BulkTransferRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'osa_code'           => 'nullable|string|max:100',
            'region_id'          => 'required|integer|exists:tbl_region,id',
            'area_id'            => 'required|integer|exists:tbl_areas,id',
            'warehouse_id'       => 'required|integer|exists:tbl_warehouse,id',
            'model_id'           => 'required|integer|exists:as_model_number,id',
            'requestes_asset'    => 'required|integer',
            'available_stock'    => 'required|integer',
        ];
    }
}
