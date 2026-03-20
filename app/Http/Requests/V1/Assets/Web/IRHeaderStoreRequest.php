<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;

class IRHeaderStoreRequest extends FormRequest
{

    public function rules()
    {
        return [
            'iro_id' => 'required|integer',
            'osa_code' => 'nullable|string|max:50',
            'salesman_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'schedule_date' => 'nullable|date',

            'details' => 'required|array|min:1',
            'details.*.fridge_id' => 'required|integer',
            'details.*.agreement_id' => 'nullable|integer',
            'details.*.crf_id' => 'nullable|integer'
        ];
    }
}
