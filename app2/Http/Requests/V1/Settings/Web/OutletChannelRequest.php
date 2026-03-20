<?php

namespace App\Http\Requests\V1\Settings\Web;

use Illuminate\Foundation\Http\FormRequest;

class OutletChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust authorization as per business rules
    }

    public function rules(): array
    {
        return [
            'outlet_channel_code' => 'sometimes',
            'outlet_channel'      => 'required|string|max:255',
            'status'              => 'in:0,1',
        ];
    }
}
