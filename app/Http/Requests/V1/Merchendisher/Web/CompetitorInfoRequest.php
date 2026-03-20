<?php

namespace App\Http\Requests\V1\Merchendisher\Web;

use Illuminate\Foundation\Http\FormRequest;

class CompetitorInfoRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'code'             => 'nullable|string|max:50|unique:competitor_infos,code',
            'company_name'     => 'required|string|max:100',
            'brand'            => 'required|string|max:50',
            'merchendiser_id'  => 'required|integer',
            'item_name'        => 'required|string|max:100',
            'price'            => 'required|numeric|min:0',
            'promotion'        => 'required|string|max:100',
            'notes'            => 'required|string',
            'image1'          => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image2'          => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required'    => 'Company name is required.',
            'brand.required'           => 'Brand is required.',
            'merchendiser_id.required' => 'Merchendiser ID is required.',
            'item_name.required'       => 'Item name is required.',
            'price.required'           => 'Price is required.',
            'price.numeric'            => 'Price must be a number.',
            'image.image'              => 'Uploaded file must be an image.',
            'image.max'                => 'Image must not exceed 2MB.',
        ];
    }
}