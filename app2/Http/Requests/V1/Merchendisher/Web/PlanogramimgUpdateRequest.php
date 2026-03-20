<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramimgUpdateRequest extends FormRequest
{
       public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id'     => 'sometimes|exists:tbl_company_customer,id',
            'merchandiser_id' => 'sometimes|integer',
            'shelf_id'        => 'sometimes|exists:shelves,id',
            'image'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
