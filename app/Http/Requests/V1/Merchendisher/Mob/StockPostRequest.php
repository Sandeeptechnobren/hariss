<?php
namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class StockPostRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

public function rules()
{
    return [
        'stocks' => 'required|array|min:1',

        'stocks.*.stock_id'      => 'required|integer',
        'stocks.*.date'          => 'nullable|date',
        'stocks.*.salesman_id'   => 'required|integer|exists:salesman,id',
        'stocks.*.customer_id'   => 'required|integer|exists:tbl_company_customer,id',
        'stocks.*.item_id'       => 'required|integer',
        'stocks.*.refill_qty'    => 'nullable|integer|min:0',
        'stocks.*.out_of_stock'  => 'nullable|boolean',
        'stocks.*.fill_qty'      => 'nullable|integer|min:0',
        'stocks.*.good_salabale' => 'nullable|integer|min:0',
    ];
}
}