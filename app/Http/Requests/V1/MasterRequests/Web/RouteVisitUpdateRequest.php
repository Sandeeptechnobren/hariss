<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RouteVisitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Main customer type (for all records)
            'customer_type' => 'nullable|integer|in:1,2',
            'merchandiser_id' => 'nullable|integer',

            // For bulk updates
            'customers' => 'nullable|array|min:1',
            'customers.*.customer_id' => 'nullable|integer',
            'customers.*.company_id' => 'nullable|string',
            'customers.*.region' => 'nullable|string',
            'customers.*.area' => 'nullable|string',
            'customers.*.warehouse' => 'nullable|string',
            'customers.*.route' => 'nullable|string',
            'customers.*.days' => 'nullable|string|max:255',
            'customers.*.from_date' => 'nullable|date',
            'customers.*.to_date' => 'nullable|date|after_or_equal:customers.*.from_date',
            'customers.*.status' => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'customers.*.customer_id.required' => 'customer_id is required for each customer record.',
            'customers.*.customer_id.uuid' => 'Each customer_id must be a valid UUID string.',
            'customers.*.to_date.after_or_equal' => 'The To Date must be after or equal to the From Date.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('customers')) {
            $customers = array_map(function ($customer) {
                foreach (['days','company_id','region','area','warehouse','route'] as $field) {
                    if (isset($customer[$field]) && is_array($customer[$field])) {
                        $customer[$field] = implode(',', $customer[$field]);
                    }
                }
                return $customer;
            }, $this->input('customers'));

            $this->merge(['customers' => $customers]);
        }
    }
}
