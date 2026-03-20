<?php

// namespace App\Http\Resources\V1\Hariss_Transaction\Web;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class POHeaderResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'                  => $this->id,
//             'uuid'                => $this->uuid,
//             'order_code'          => $this->order_code,

//             'customer_id'         => $this->customer_id,
//             'customer_code'       => $this->customer->osa_code ?? null,
//             'customer_name'       => $this->customer->business_name ?? null,
//             'customer_town'       => $this->customer->town ?? null,
//             'customer_district'     => $this->customer->district ?? null,
//             'customer_contact'    => $this->customer->contact_number ?? null,

//             'salesman_id'         => $this->salesman_id,
//             'salesman_code'       => $this->salesman->osa_code ?? null,
//             'salesman_name'       => $this->salesman->name ?? null,

//             'company_id'          => $this->company_id,
//             'company_code'        => $this->company->company_code ?? null,
//             'company_name'        => $this->company->company_name ?? null,
//             'company_email'       => $this->company->email ?? null,

//             'warehouse_id'        => $this->warehouse_id,
//             'warehouse_code'      => $this->warehouse->warehouse_code ?? null,
//             'warehouse_name'      => $this->warehouse->warehouse_name ?? null,

//             'delivery_date'       => $this->delivery_date,
//             'comment'             => $this->comment,
//             'status'              => $this->status,

//             'gross_total'         => (float) $this->gross_total,
//             'pre_vat'             => (float) $this->pre_vat,
//             'discount'            => (float) $this->discount,
//             'net_amount'          => (float) $this->net_amount,
//             'total'               => (float) $this->total,
//             'excise'              => (float) $this->excise,
//             'vat'                 => (float) $this->vat,

//             'sap_id'              => $this->sap_id,
//             'sap_msg'             => $this->sap_msg,

//             // DETAILS (just like your OrderHeaderResource)
//             'details'             => PODetailResource::collection($this->whenLoaded('details')),

//             // Prev/Next
//             'previous_uuid'       => $this->previous_uuid ?? null,
//             'next_uuid'           => $this->next_uuid ?? null,

//             'created_at'          => $this->created_at,
//         ];
//     }
// }

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class POHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = auth()->user();

        $workflowRequest = DB::table('htapp_workflow_requests')
            ->where('process_type', 'Po_Order_Header')
            ->where('process_id', $this->id)
            ->latest()
            ->first();

        $approvalStatus = 'Auto Approved';
        $currentStep = null;
        $currentStepId = null;
        $progress = null;
        $canApprove = false;

        if ($workflowRequest) {

            $current = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $total = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->count();

            $approved = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderByDesc('step_order')
                ->first();

            $approvalStatus = $lastApprovedStep->message ?? 'Initiated';
            $currentStep = $current->title ?? null;
            $currentStepId = $current->id ?? null;
            $progress = $total > 0 ? ($approved . '/' . $total) : null;

            if ($current && $user) {
                $canApprove = DB::table('htapp_workflow_request_step_approvers')
                    ->where('request_step_id', $current->id)
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->orWhere('role', $user->role);
                    })
                    ->where('has_approved', false)
                    ->exists();
            }
        }

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'order_code'    => $this->order_code,

            'customer_id'   => $this->customer_id,
            'customer_code' => $this->customer->osa_code ?? null,
            'customer_name' => $this->customer->business_name ?? null,
            'customer_town' => $this->customer->town ?? null,
            'customer_district' => $this->customer->district ?? null,
            'customer_contact'  => $this->customer->contact_number ?? null,

            'salesman_id'   => $this->salesman_id,
            'salesman_code' => $this->salesman->osa_code ?? null,
            'salesman_name' => $this->salesman->name ?? null,

            'company_id'    => $this->company_id,
            'company_code'  => $this->company->company_code ?? null,
            'company_name'  => $this->company->company_name ?? null,
            'company_email' => $this->company->email ?? null,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,

            'delivery_date' => $this->delivery_date,
            'comment'       => $this->comment,
            'status'        => $this->status,

            'gross_total'   => (float) $this->gross_total,
            'pre_vat'       => (float) $this->pre_vat,
            'discount'      => (float) $this->discount,
            'net'           => (float) $this->net,
            'total'         => (float) $this->total,
            'excise'        => (float) $this->excise,
            'vat'           => (float) $this->vat,

            'sap_id'        => $this->sap_id,
            'sap_msg'       => $this->sap_msg,
            'order_date'       => $this->order_date,

            'details'       => PODetailResource::collection(
                $this->whenLoaded('details')
            ),

            // ✅ OLD FORMAT (AS REQUESTED)
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
            'can_approve'     => $canApprove,

            'previous_uuid' => $this->previous_uuid ?? null,
            'next_uuid'     => $this->next_uuid ?? null,
            'created_at'    => $this->created_at,
        ];
    }
}


