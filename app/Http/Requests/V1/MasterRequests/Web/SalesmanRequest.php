<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesmanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if you want to restrict based on auth
    }

    public function rules(): array
    {
         $uuid = $this->route('uuid'); // for update

        return [
            'osa_code' => ['sometimes','string','max:50',Rule::unique('salesman', 'osa_code')->ignore($uuid, 'uuid'),],
            'name'  => 'nullable|string|max:50',
            'type'  => 'required|exists:salesman_types,id',
            'designation'    => 'required|string|max:150',
            'route_id'       => 'sometimes|integer',
            'password'   => 'nullable|string|max:150',
            'contact_no'     => 'nullable|string|max:20',
            'warehouse_id'   => ['required', 'string'],
            'email'     => 'nullable|email|max:100',
            'status'         => 'required|integer|in:0,1',
            'is_take'         => 'nullable|integer|in:0,1',
            'forceful_login'    => 'required|integer|in:0,1',
            'is_block'          => 'nullable|integer|in:0,1',
            'block_date_from' => ['nullable', 'date',Rule::requiredIf(function () {return request()->input('is_block') == 1;}), ],
            'block_date_to' => ['nullable', 'date', 'after_or_equal:block_date_from',Rule::requiredIf(function () {return request()->input('is_block') == 1;}),],
            'reason' => ['nullable', 'string', 'max:250',Rule::requiredIf(function () {return request()->input('invoice_block') == 1; }),],
            'cashier_description_block' => 'nullable|integer|in:0,1',
            'invoice_block'             => 'nullable|integer|in:0,1',

            // 'is_login'       => 'nullable|integer|in:0,1',
            // 'sub_type'       => 'nullable|in:0,1,2',
            // 'salesman_role'  => 'nullable|integer',
            // 'security_code'  => 'nullable|string|max:50',
            // 'device_no'      => 'nullable|string|max:50',
            // 'token_no'       => 'required|string|max:10',
            // 'sap_id'         => 'nullable|string|max:55',
            // 'username'       => 'required|string|max:55|unique:salesman,username',
  
        ];
    }
}
            
                
           
       
            
                
            
        
            
            
                
         