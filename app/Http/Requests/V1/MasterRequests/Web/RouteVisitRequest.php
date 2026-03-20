<?php

namespace App\Http\Requests\V1\MasterRequests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouteVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_type' => 'required|integer|in:1,2',
            'merchandiser_id' => 'nullable|integer',

            // Multiple customers
            'customers' => 'nullable|array|min:1',
            'customers.*.customer_id' => 'required|integer',
            'customers.*.company_id' => 'nullable|string',
            'customers.*.region' => 'nullable|string',
            'customers.*.area' => 'nullable|string',
            'customers.*.warehouse' => 'nullable|string',
            'customers.*.route' => 'nullable|string',
            'customers.*.days' => 'nullable|string|max:255',
            'customers.*.from_date' => 'required|date',
            'customers.*.to_date' => 'required|date|after_or_equal:customers.*.from_date',
            'customers.*.status' => 'nullable|integer|in:0,1',

            // Single record (fallback)
            'customer_id' => 'nullable|integer',
            'company_id' => 'nullable|string',
            'region' => 'nullable|string',
            'area' => 'nullable|string',
            'warehouse' => 'nullable|string',
            'route' => 'nullable|string',
            'days' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => 'nullable|integer|in:0,1',
            

            'osa_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('route_visit', 'osa_code')->ignore($this->route('id'))
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_type.required' => 'Customer type is required (1 = agent, 2 = merchandisor).',
            'customers.*.customer_id.required' => 'Customer ID is required for each entry.',
            'customers.*.to_date.after_or_equal' => 'The To Date must be after or equal to the From Date.',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert nested arrays to comma-separated strings
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

        // Handle single record case
        foreach (['days','company_id','region','area','warehouse','route'] as $field) {
            $value = $this->input($field);
            if (is_array($value)) {
                $this->merge([$field => implode(',', $value)]);
            }
        }
    }
}
