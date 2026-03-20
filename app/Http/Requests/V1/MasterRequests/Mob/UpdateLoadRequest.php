<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'salesman_sign' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'accept_time'   => 'nullable|date_format:Y-m-d H:i:s',
            'load_id'       => 'nullable|integer',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'sync_time'     => 'nullable|date_format:Y-m-d H:i:s',
        ];
    }
}