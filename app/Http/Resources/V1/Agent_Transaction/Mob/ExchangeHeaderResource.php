<?php

namespace App\Http\Resources\V1\Agent_Transaction\Mob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class ExchangeHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Exchange_Header')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep = null;
        $currentStepId = null;
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
            $currentStepId = $current?->id;
            $progress      = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
        }

        return [
            'id'             => $this->id,
            'exchange_code'  => $this->exchange_code,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,

            'customer_id'    => $this->customer_id,
            'customer_code'  => $this->customer->osa_code ?? null,
            'customer_name'  => $this->customer->name ?? null,

            'comment'        => $this->comment,
            'status'         => $this->status,
            'latitude'       => $this->latitude,
            'longitude'      => $this->longitude,

            'invoices'       => ExchangeDetailResource::collection($this->whenLoaded('invoices')),
            'returns'        => ExchangeDetailResource::collection($this->whenLoaded('returns')),

            // ==================================================
            // ?? Approval Workflow Status
            // ==================================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
        ];
    }
}
