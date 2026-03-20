<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintFeedbackRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'complaint_title' => 'required|string|max:255',
            'item_id' => 'required|exists:items,id',
            'merchendiser_id' => 'required|exists:salesman,id',
            'customer_id' => 'required|exists:tbl_company_customer,id',
            'type' => 'nullable|string|max:255',
            'complaint' => 'required|string',
            // 'uuid' => 'required|uuid',
            'complaint_code' => 'nullable|string|max:100',
           'image' => 'required|array|size:2',
           'image.*' => 'required|file|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}