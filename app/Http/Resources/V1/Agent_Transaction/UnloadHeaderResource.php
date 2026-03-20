<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class UnloadHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        /**
         * =======================================================
         * 🔥 Approval workflow status (SAVED PATTERN)
         * =======================================================
         */
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Unload_Header')
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

            $totalSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->count();

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
            $progress      = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'unload_no' => $this->unload_no,
            'unload_date' => $this->unload_date,
            'unload_time' => $this->unload_time,
            'sync_date' => $this->sync_date,
            'sync_time' => $this->sync_time,
            // 'salesman_type' => $this->salesman_type,
            'salesman_type' => $this->salesmantype ? [
                'id' => $this->salesmantype->id,
                // 'code' => $this->salesmantype->code,
                'name' => $this->salesmantype->salesman_type_name
            ] : null,
            'project_type' => $this->projecttype ? [
                'id' => $this->projecttype->id,
                // 'code' => $this->projecttype->salesman_type_code,
                'name' => $this->projecttype->name
            ] : null,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name
            ] : null,

            'route' => $this->route ? [
                'id' => $this->route->id,
                'code' => $this->route->route_code,
                'name' => $this->route->route_name
            ] : null,

            'salesman' => $this->salesman ? [
                'id' => $this->salesman->id,
                'code' => $this->salesman->osa_code,
                'name' => $this->salesman->name
            ] : null,

            'latitude' => $this->latitude,
            'longtitude' => $this->longtitude,
            'unload_from' => $this->unload_from,
            'load_date' => $this->load_date,
            'status' => $this->status,

            'details' => UnloadDetailResource::collection($this->whenLoaded('details')),

            // ==========================================
            // 🚀 Approval fields added
            // ==========================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
        ];
    }
}
