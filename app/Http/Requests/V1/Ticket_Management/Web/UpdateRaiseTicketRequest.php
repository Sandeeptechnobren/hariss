<?php

namespace App\Http\Requests\V1\Ticket_Management\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRaiseTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'attachment' => 'sometimes|array',
            'attachment.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
            // 'attachment' => 'sometimes|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
            'comment' => 'sometimes|string',
            'device_detail' => 'sometimes|string|max:255',
            'time_to_resolve' => 'sometimes|date',
            'role_id' => 'sometimes|integer',
            'user_id' => 'sometimes|integer',
            'issue_type' => 'sometimes|integer',
            'priority' => 'sometimes|integer',
            'severity' => 'sometimes|integer',
            'status' => 'sometimes|integer',
            'customer' => 'sometimes|integer|exists:agent_customers,id',
            'companyCustomer' => 'sometimes|integer|exists:tbl_company_customer,id',
            'salesman'  => 'sometimes|integer|exist:salesman,id',
        ];
    }
}
