<?php

namespace App\Http\Requests\V1\Hariss_Transaction\Web;

use Illuminate\Foundation\Http\FormRequest;

class PoOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_code'    => 'nullable|string|unique:ht_po_order_header,order_code',
            'sap_id'        => 'nullable|string|max:100',
            'sap_msg'       => 'required|string',
            'customer_id'   => 'required|integer|exists:tbl_company_customer,id',
            'delivery_date' => 'nullable|date',
            'comment'       => 'nullable|string',
            'status'        => 'nullable|integer|in:0,1',
            'currency'      => 'nullable|string|max:20',
            'country_id'    => 'nullable|integer|exists:tbl_country,id',
            'salesman_id'   => 'nullable|integer|exists:salesman,id',
            'gross_total'   => 'nullable|numeric',
            'discount'      => 'nullable|numeric',
            'pre_vat'       => 'nullable|numeric',
            'vat'           => 'nullable|numeric',
            'excise'        => 'nullable|numeric',
            'net'           => 'nullable|numeric',
            'total'         => 'nullable|numeric',
            'order_flag'    => 'nullable|integer|in:0,1',
            'log_file'      => 'nullable|string|max:255',
            'doc_type'      => 'nullable|string|max:255',
            'order_date'    => 'nullable|date',

            // Details (array)
            'details'                       => 'required|array|min:1',
            'details.*.item_id'             => 'required|integer|exists:items,id',
            'details.*.uom_id'              => 'required|integer|exists:uom,id',
            'details.*.discount_id'         => 'nullable|integer',
            'details.*.promotion_id'        => 'nullable|integer',
            'details.*.parent_id'           => 'nullable|integer',
            'details.*.item_price'          => 'nullable|numeric',
            'details.*.quantity'            => 'required|integer',
            'details.*.discount'            => 'nullable|numeric',
            'details.*.gross_total'         => 'nullable|numeric',
            'details.*.promotion'           => 'nullable|boolean',
            'details.*.net'                 => 'nullable|numeric',
            'details.*.excise'              => 'nullable|numeric',
            'details.*.pre_vat'             => 'nullable|numeric',
            'details.*.vat'                 => 'nullable|numeric',
            'details.*.total'               => 'nullable|numeric',
        ];
    }
}
