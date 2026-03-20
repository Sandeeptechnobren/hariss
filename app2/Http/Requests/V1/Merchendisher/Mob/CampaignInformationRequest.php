<?php

namespace App\Http\Requests\V1\Merchendisher\Mob;

use Illuminate\Foundation\Http\FormRequest;

class CampaignInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_time' => 'nullable|date',
            'merchandiser_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'feedback' => 'required|string',
            'image_1' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'image_2' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}