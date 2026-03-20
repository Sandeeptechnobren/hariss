<?php

namespace App\Http\Requests\V1\Assets\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Set to true if all authenticated users can create/update vendors
        return true;
    }

    public function rules(): array
    {
        $vendorId = $this->route('id'); // For unique validation on update

        return [
            'code' => 'sometimes|string|max:200|unique:tbl_vendor,code,' . $vendorId,
            'name' => 'required|string|max:200',
            'address'     => 'nullable|string|max:200',
            'contact'     => 'nullable|string|regex:/^[0-9]{7,15}$/',
            'email' => [
                'sometimes',
                'email',
                'max:200',
                Rule::unique('tbl_vendor', 'email')->ignore($vendorId),
            ],
            'status'      => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vendor code is required.',
            'code.unique'   => 'Vendor code must be unique.',
            'name.required' => 'Vendor name is required.',
            'contact.regex'        => 'Contact number must be valid.',
            'email.email'          => 'Email must be valid.',
            'status.in'            => 'Status must be 0 (Active) or 1 (Inactive).',
        ];
    }
}
