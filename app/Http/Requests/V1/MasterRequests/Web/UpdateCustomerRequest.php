<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'osa_code' => 'sometimes|string|max:100|unique:customermaster,osa_code,' . $this->route('uuid') . ',uuid',
            'code' => 'sometimes|string|max:200',
            'name' => 'sometimes|string|max:50',
            'customer_type' => 'sometimes|exists:customer_types,id',
             'route_id' => 'sometimes|exists:tbl_route,id',
            'street' => 'sometimes|string|max:255',
            'customersequence' => 'sometimes|integer',
            'owner_name' => 'sometimes|string|max:200',
            'email' => 'sometimes|email|max:200',
            'language' => 'sometimes|string|max:20',
            'fridge' => 'sometimes|string|max:100',
            'buyerType' => 'sometimes|integer|in:0,1',
            'ura_address' => 'sometimes|string',
            'address_1' => 'sometimes|string|max:50',
            'address_2' => 'sometimes|string|max:50',
            'phone_1' => 'sometimes|string|max:20',
            'phone_2' => 'sometimes|string|max:20',
            'balance' => 'sometimes|numeric|min:0',
            'customer_category' => 'sometimes|exists:customer_categories,id',
            'customer_sub_category' => 'sometimes|exists:customer_sub_categories,id',
            'outlet_channel_id' => 'sometimes|exists:outlet_channel,id',
            'pricingkey' => 'sometimes|integer',
            'promotionkey' => 'sometimes|integer|nullable',
            'authorizeditemgrpkey' => 'sometimes|integer',
            'paymentmethod' => 'sometimes|integer|nullable',
            'payment_type' => 'sometimes|integer',
            'bank_name' => 'sometimes|string|max:255',
            'bank_account_number' => 'sometimes|string|max:255',
            'creditday' => 'sometimes|string|max:255',
            'salesopt' => 'sometimes|integer',
            'returnsopt' => 'sometimes|integer',
            'surveykey' => 'sometimes|integer',
            'customertype' => 'sometimes|string|max:1',
            'callfrequency' => 'sometimes|integer',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:50',
            'customerzip' => 'sometimes|string|max:10',
            'invoicepriceprint' => 'sometimes|integer',
            'enablepromotrxn' => 'sometimes|integer',
            'trn_no' => 'sometimes|string|max:255',
            'accuracy' => 'sometimes|string|max:50',
            'creditlimit' => 'sometimes|numeric|min:0',
            'expirylimit' => 'sometimes|numeric|nullable',
            'exprunningvalue' => 'sometimes|numeric|nullable',
            'barcode' => 'sometimes|string|max:20|nullable',
            'division' => 'sometimes|string|max:50',
            'price_survey_id' => 'sometimes|integer|nullable',
            'allowchequecollection' => 'sometimes|integer|nullable',
            'region_id' => 'sometimes|exists:tbl_region,id',
            'area_id' => 'sometimes|exists:tbl_areas,id',
            'vat_no' => 'sometimes|string|max:30',
            'longitude' => 'sometimes|string|nullable',
            'latitude' => 'sometimes|string|nullable',
            'threshold_radius' => 'sometimes|integer',
            'salesman_id' => 'sometimes|exists:salesman,id',
            'status' => 'sometimes|integer|in:0,1',
            'print_status' => 'sometimes|boolean',
            'guarantee_name' => 'sometimes|string|max:500',
            'guarantee_amount' => 'sometimes|numeric|min:0',
            'guarantee_from' => 'sometimes|date',
            'guarantee_to' => 'sometimes|date',
            'givencreditlimit' => 'sometimes|numeric|min:0',
            'qrcode_image' => 'sometimes|string|max:255',
            'qr_value' => 'sometimes|integer',
            'qr_latitude' => 'sometimes|string|max:100|nullable',
            'qr_longitude' => 'sometimes|string|max:100|nullable',
            'qr_accuracy' => 'sometimes|string|max:100|nullable',
            'capital_invest' => 'sometimes|numeric|min:0',
            'sap_id' => 'sometimes|string|max:200|nullable',
            'dchannel_id' => 'sometimes|integer',
            'last_updated_serial_no' => 'sometimes|integer|nullable',
            'credit_limit_validity' => 'sometimes|date|nullable',
            'invoice_code' => 'sometimes|string|max:20',
            'fridge_id' => 'sometimes|exists:tbl_add_chillers,id',
            'installation_date' => 'sometimes|date',
            'is_fridge_assign' => 'sometimes|integer',
            'serial_number_temp' => 'sometimes|string|max:250',
        ];
    }
}
