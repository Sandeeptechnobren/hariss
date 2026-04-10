<?php
namespace App\Http\Requests\V1\Hariss_Transaction\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditNoteRequest extends FormRequest
{
    public function rules()
    {
        return [
            'credit_note_no' => 'required|string|unique:credit_note_headers,credit_note_no',
            'purchase_invoice_id' => 'nullable|exists:ht_invoice_header,id',
            'supplier_id' => 'nullable|exists:ht_invoice_header,sap_id',
            'customer_id' => 'nullable|exists:ht_invoice_header,customer_id',
            'salesman_id' => 'nullable|exists:ht_invoice_header,salesman_id',
            'distributor_id' => 'nullable|exists:ht_invoice_header,warehouse_id',
            'reason' => 'nullable|string',

            'details' => 'required|array|min:1',
            'details.*.item_id' => 'required|exists:ht_invoice_detail,item_id',
            'details.*.qty' => 'required|numeric|min:0.01',
            'details.*.price' => 'required|numeric|min:0',
        ];
    }
}