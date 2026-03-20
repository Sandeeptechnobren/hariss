<?php

namespace App\Http\Requests\V1\Ticket_Management\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreRaiseTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|array',
            'attachment.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
            // 'attachment' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
            'device_detail' => 'nullable|string|max:255',
            'role_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'time_to_resolve' => 'nullable|date',
            'status' => 'nullable|integer',
            'issue_type' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'severity' => 'nullable|integer',
            'customer' => 'nullable|integer|exists:agent_customers,id',
            'companyCustomer' => 'nullable|integer|exists:tbl_company_customer,id',
            'salesman' => 'nullable|integer|exists:salesman,id',
        ];
    }
}
