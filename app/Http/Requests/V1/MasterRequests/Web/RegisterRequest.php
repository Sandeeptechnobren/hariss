<?php

namespace App\Http\Requests\V1\MasterRequests\Web;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public endpoint
        return true;
    }

    public function rules(): array
    {
        return [ 
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username'          => ['required', 'string', 'max:255', 'unique:users,username'],
            'contact_number'    => ['required', 'string', 'max:255'],
            // 'password'          => ['required', 'string', 'min:6', 'confirmed'],
            'password' => ['required','string','confirmed',Password::min(12)
                            ->mixedCase()
                            ->letters()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                            ],
            'profile_picture'   => ['nullable', 'string', 'max:255'],
            'role'              => ['required', 'integer', 'exists:roles,id'],
            'status'            => ['nullable', 'integer'],
            'company'           => ['nullable', 'array'],
            'company.*'         => ['integer', 'exists:tbl_company,id'],
            'warehouse'         => ['nullable', 'array'],
            'warehouse.*'       => ['integer', 'exists:tbl_warehouse,id'],
            'route'             => ['nullable', 'array'],
            'route.*'           => ['integer', 'exists:tbl_route,id'],
            'salesman'          => ['nullable', 'array'],
            'salesman.*'        => ['integer', 'exists:salesman,id'],
            'region'            => ['nullable', 'array'],
            'region.*'          => ['integer', 'exists:tbl_region,id'],
            'area'              => ['nullable', 'array'],
            'area.*'            => ['integer', 'exists:tbl_areas,id'],
            'outlet_channel'    => ['nullable', 'array'],
            'outlet_channel.*'  => ['integer', 'exists:outlet_channel,id'],
            'created_by'      => ['nullable', 'integer','exists:users,id'],
            'updated_user'      => ['nullable', 'integer'],
            'Created_Date'      => ['nullable', 'date'],
        ]; 
    }
}
