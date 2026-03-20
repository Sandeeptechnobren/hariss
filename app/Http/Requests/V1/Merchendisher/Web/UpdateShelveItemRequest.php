<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShelveItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'shelf_id' => 'sometimes|integer',
            'product_id' => 'sometimes|integer',
            'capacity' => 'sometimes|numeric',
            'status' => 'sometimes|string|max:50',
            'total_no_of_fatching' => 'nullable|numeric',
        ];
    }
}
