<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Resources\Json\JsonResource;

// class AdvancePaymentResource extends JsonResource
// {
//     public function toArray($request)
//     {
//         return [
//             'id' => $this->id,
//             'uuid' => $this->uuid,
//             'osa_code' => $this->osa_code,
//             'payment_type' => $this->payment_type,
//             'payment_type_text' => match($this->payment_type) {
//                 1 => 'Cash',
//                 2 => 'Cheque',
//                 3 => 'Transfer',
//                 default => null,
//             },
//             'companybank_id' => $this->companybank_id,
//             'account_number' => $this->companyBank->account_number ?? null,
//             'bank_name'      => $this->companyBank->bank_name ?? null,
//             'branch'         => $this->companyBank->branch ?? null,
//             'agent_id' => $this->agent_id,
//             'Agent_bank_name' => $this->agent->bank_guarantee_name ?? null,
//             'bank_account_number' => $this->agent->bank_account_number ?? null,
//             'amount' => $this->amount,
//             'recipt_no' => $this->recipt_no,
//             'recipt_date' => $this->recipt_date?->format('Y-m-d'),
//             'recipt_image' => $this->recipt_image ,//? asset($this->recipt_image) : null,
//             'cheque_no' => $this->cheque_no,
//             'cheque_date' => $this->cheque_date?->format('Y-m-d'),
//             'status' => $this->status,
//         ];
//     }
// }

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class AdvancePaymentResource extends JsonResource
{
    public function toArray($request)
    {
        /**
         * ==========================================
         * 🔥 Approval Workflow Status (Saved Pattern)
         * ==========================================
         */
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Distributor_Advance_Payment')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep = null;
        $requestStepId = null;
        $progress = null;

        if ($workflowRequest) {

            $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completedSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'DESC')
                ->first();

            $approvalStatus = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $currentStep   = $current?->title;
            $requestStepId = $current?->id;
            $progress      = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'payment_type' => $this->payment_type,
            'payment_type_text' => match($this->payment_type) {
                1 => 'Cash',
                2 => 'Cheque',
                3 => 'Transfer',
                default => null,
            },
            'companybank_id' => $this->companybank_id,
            'account_number' => $this->companyBank->account_number ?? null,
            'bank_name'      => $this->companyBank->bank_name ?? null,
            'branch'         => $this->companyBank->branch ?? null,
            'agent_id' => $this->agent_id,
            'Agent_bank_name' => $this->agent->bank_guarantee_name ?? null,
            'bank_account_number' => $this->agent->bank_account_number ?? null,
            'amount' => $this->amount,
            'recipt_no' => $this->recipt_no,
            'recipt_date' => $this->recipt_date?->format('Y-m-d'),
            'recipt_image' => $this->recipt_image,
            'cheque_no' => $this->cheque_no,
            'cheque_date' => $this->cheque_date?->format('Y-m-d'),
            'status' => $this->status,

            // ==========================================
            // 🚀 Approval Info Added
            // ==========================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $requestStepId,
            'progress'        => $progress,
        ];
    }
}
