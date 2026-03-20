<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class PlanogramImageRequest extends FormRequest
{
   public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id'     => 'required|exists:tbl_company_customer,id',
            'merchandiser_id' => 'required|integer',
            'shelf_id'        => 'required|exists:shelves,id',
            'image'           => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
