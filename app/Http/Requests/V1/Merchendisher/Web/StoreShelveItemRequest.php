<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreShelveItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'shelf_id' => 'required|integer',
            'product_id' => 'required|integer|exists:items,id',
            'capacity' => 'required|numeric',
            'total_no_of_fatching' => 'nullable|numeric',
        ];
    }
}
