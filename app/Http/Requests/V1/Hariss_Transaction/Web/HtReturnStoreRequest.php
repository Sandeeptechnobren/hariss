<?php

namespace App\Http\Requests\V1\Hariss_Transaction\Web;

use Illuminate\Foundation\Http\FormRequest;

class HtReturnStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_code'   => 'nullable|string|unique:ht_return_header,return_code',
            'customer_id'   => 'nullable|integer|exists:tbl_company_customer,id',
            'turnman'       => 'nullable|string|max:100',
            'truck_no'      => 'nullable|string|max:50',
            'contact_no'    => 'nullable|string|max:30',
            'return_no'     => 'nullable|string|max:50',
            'total'         => 'required|numeric',
            'comment'       => 'nullable|string',
            'status'        => 'required|integer',
            'company_id'    => 'nullable|integer|exists:tbl_company,id',
            'warehouse_id'  => 'required|integer|exists:tbl_warehouse,id',
            'driver_id'     => 'nullable|integer|exists:drivers,id',
            'net'           => 'required|numeric',
            'vat'           => 'required|numeric',
            'sap_id'        => 'nullable|integer',

            'details'                       => 'required|array|min:1',
            'details.*.item_id'             => 'required|integer|exists:items,id',
            'details.*.item_price'          => 'required|numeric',
            'details.*.quantity'            => 'required|numeric|min:1',
            'details.*.vat'                 => 'nullable|numeric',
            'details.*.uom_id'              => 'required|integer|exists:uom,id',
            'details.*.net_total'           => 'nullable|numeric',
            'details.*.total'               => 'required|numeric',
            'details.*.batch_number'        => 'nullable|string|max:50',
            'details.*.expiry_date'         => 'nullable|date',
            'details.*.type'                => 'required|in:good,bad',
            'details.*.reason'              => 'nullable|string|max:50',
            'details.*.posnr'               => 'nullable|string',
            'details.*.invoice_sap_id'      => 'nullable|integer',
            'details.*.return_date'         => 'nullable|date',
        ];
    }
}
