<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class AllocateAssetsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id'           => 'required|integer|exists:tbl_bulk_transfer_request,id',
            'warehouse_id'  => 'required|integer|exists:tbl_warehouse,id',
            'truck_no'      => 'required|string|max:255',
            'turnmen_name'  => 'required|string|max:255',
            'contact'       => 'required|string|max:25',
            'checked_data'  => 'required|array|min:1',
            'checked_data.*' => 'integer|exists:tbl_add_chillers,id',
        ];
    }
}
