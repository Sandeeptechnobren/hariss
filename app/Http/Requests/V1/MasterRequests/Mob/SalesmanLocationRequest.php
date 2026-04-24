<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanLocationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
    return [
        'salesman_id'        => 'required|integer|exists:salesman,id',
        'warehouse_id'       => 'nullable|integer',
        'route_id'           => 'nullable|integer',

        'location'          => 'required|array|min:1',
        'location.*.lat'    => 'required|numeric',
        'location.*.lng'    => 'required|numeric',
        'location.*.time'   => 'required|date',
    ];
    }
}
