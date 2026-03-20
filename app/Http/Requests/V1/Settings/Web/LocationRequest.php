<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize()
    {
        // change as per your auth / policy needs
        return true;
    }

    public function rules()
    {
        $locationId = $this->route('location') ? $this->route('location')->id : null;

        return [
            'name' => 'required|string|max:255',
             'code' => 'nullable|string|max:50',
            // uuid should not be provided/changed from client normally
        ];
    }
}
