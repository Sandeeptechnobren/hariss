<?php

namespace App\Http\Requests\V1\Settings\Web;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize() {
        return true;
    }

    public function rules() {
        return [
            'name' => 'required|string|unique:permissions,name'
        ];
    }
}
