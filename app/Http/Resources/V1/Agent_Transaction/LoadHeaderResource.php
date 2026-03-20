<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Resources\Json\JsonResource;

// class LoadHeaderResource extends JsonResource
// {
//     public function toArray($request)
//     {
//         return [
//             'id' => $this->id,
//             'uuid' => $this->uuid,
//             'osa_code' => $this->osa_code,
//             // 'salesman_type' => $this->salesman_type,
//             'salesman_type' => $this->salesmantype ? [
//                 'id' => $this->salesmantype->id,
//                 'code' => $this->salesmantype->salesman_type_code,
//                 'name' => $this->salesmantype->salesman_type_name
//             ] : null,
//             'project_type' => $this->projecttype ? [
//                 'id' => $this->projecttype->id,
//                 'code' => $this->projecttype->osa_code,
//                 'name' => $this->projecttype->name
//             ] : null,
//             'warehouse' => $this->warehouse ? [
//                 'id' => $this->warehouse->id,
//                 'code' => $this->warehouse->warehouse_code,
//                 'name' => $this->warehouse->warehouse_name
//             ] : null,
//             // 'route_id' => $this->route_id,
//             'route' => $this->route ? [
//                 'id' => $this->route->id,
//                 'code' => $this->route->route_code,
//                 'name' => $this->route->route_name
//             ] : null,
//             // 'salesman_id' => $this->salesman_id,
//             'salesman' => $this->salesman ? [
//                 'id' => $this->salesman->id,
//                 'code' => $this->salesman->osa_code,
//                 'name' => $this->salesman->name
//             ] : null,
//             // 'projecttype' => $this->projecttype ? [
//             //     'id' => $this->projecttype->id,
//             //     'code' => $this->projecttype->salesman_type_code,
//             //     'name' => $this->projecttype->salesman_type_name
//             // ] : null,
//             'is_confirmed' => $this->is_confirmed,
//             'accept_time' => $this->accept_time,
//             'salesman_sign' => $this->salesman_sign,
//             'latitude' => $this->latitude,
//             'longtitude' => $this->longtitude,
//             'status' => $this->status,
//             'details' => LoadDetailResource::collection($this->whenLoaded('details')),
//         ];
//     }
// }
namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class LoadHeaderResource extends JsonResource
{
    public function toArray($request)
    {
        /**
         * =======================================================
         * 🔥 Approval workflow status (SAVED PATTERN)
         * =======================================================
         */
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Load_Header')
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

            $currentStep   = $current ? $current->title : null;
            $currentStepId = $current ? $current->id : null;

            $progress = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,

            'salesman_type' => $this->salesmantype ? [
                'id' => $this->salesmantype->id,
                'code' => $this->salesmantype->salesman_type_code,
                'name' => $this->salesmantype->salesman_type_name
            ] : null,

            'project_type' => $this->projecttype ? [
                'id' => $this->projecttype->id,
                'code' => $this->projecttype->osa_code,
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

            'is_confirmed' => $this->is_confirmed,
            'accept_time' => $this->accept_time,
            'salesman_sign' => $this->salesman_sign,
            'latitude' => $this->latitude,
            'longtitude' => $this->longtitude,
            'status' => $this->status,
            'created_at' => $this->created_at,

            'details' => LoadDetailResource::collection($this->whenLoaded('details')),

            // ==========================================
            // 🚀 Approval Workflow Fields
            // ==========================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
        ];
    }
}
