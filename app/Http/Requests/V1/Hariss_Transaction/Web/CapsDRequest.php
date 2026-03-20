<?php

namespace App\Http\Requests\V1\Hariss_Transaction\Web;

use Illuminate\Foundation\Http\FormRequest;

class CapsDRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [ 
            'osa_code'        => 'nullable|string|unique:ht_caps_header,osa_code',
            'warehouse_id'    => 'required|integer|exists:tbl_warehouse,id',
            'driver_id'       => 'required|integer|exists:drivers,id',
            'truck_no'        => 'required|string',
            'contact_no'      => 'required|string',
            'claim_no'        => 'required|string',
            'claim_date'      => 'required|date',
            'claim_amount'    => 'required|numeric',
            'status'          => 'nullable|in:0,1',

            'details'                       => 'required|array|min:1',
            'details.*.osa_code'            => 'nullable|unique:ht_caps_details,osa_code',
            'details.*.item_id'             => 'required|integer|exists:items,id',
            'details.*.uom_id'              => 'required|integer|exists:uom,id',
            'details.*.quantity'            => 'nullable|numeric',
            'details.*.receive_qty'         => 'nullable|numeric',
            'details.*.receive_amount'      => 'nullable|numeric',
            'details.*.receive_date'        => 'nullable|date',
            'details.*.remarks'             => 'nullable|string',
            'details.*.remarks2'            => 'nullable|string',
            'details.*.status'              => 'nullable|in:0,1',
        ];
    }
}
