<?php

namespace App\Http\Requests\V1\MasterRequests\Mob;

use Illuminate\Foundation\Http\FormRequest;

class SalesmanRequest extends FormRequest
{
     public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'salesman_id'   => 'required|integer',
            'route_id'      => 'required|integer',
            'warehouse_id'  => 'required|integer',
            'manager_id'    => 'required|integer',
            'requested_time'=> 'required|string',
            'requested_date'=> 'required|date',
        ];
    }
}