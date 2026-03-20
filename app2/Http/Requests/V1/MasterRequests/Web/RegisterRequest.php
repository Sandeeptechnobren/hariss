<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        return [
            'firstname'     => ['required', 'string', 'max:255'],
            'lastname'      => ['required', 'string', 'max:255'],
            'username'      => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:6', 'confirmed'], // expects password_confirmation
            'profile'       => ['nullable', 'string', 'max:255'],
            'role'          => ['nullable', 'integer'],   // 0=Super-Admin, 1=Admin , 2 = User
            'status'        => ['nullable', 'integer'],   // default active
            'region_id'     => ['nullable', 'string', 'max:255'],
            'subregion_id'  => ['nullable', 'string', 'max:255'],
            'salesman_id'   => ['nullable', 'integer'],
            'subdepot_id'   => ['nullable', 'string', 'max:200'],
            'Modifier_Id'   => ['nullable', 'integer'],
            'Modifier_Name' => ['nullable', 'string', 'max:255'],
            'Modifier_Date' => ['nullable', 'date'],
            'Login_Date'    => ['nullable', 'date'],
            'is_list'       => ['nullable', 'integer'],   // 1=yes,0=no
            'created_user'  => ['nullable', 'integer'],
            'updated_user'  => ['nullable', 'integer'],
            'Created_Date'  => ['nullable', 'date'],
        ];
    }
}
