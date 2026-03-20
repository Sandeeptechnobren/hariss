<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class RouteExpenceResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id' => $this->id,
//             'uuid' => $this->uuid,
//             'osa_code' => $this->osa_code,
//             'warehouse' => $this->warehouse ? [
//                 'id' => $this->warehouse->id,
//                 'code' => $this->warehouse->warehouse_code,
//                 'name' => $this->warehouse->warehouse_name
//             ] : null,
//             'route' => $this->route ? [
//                 'id' => $this->route->id,
//                 'code' => $this->route->route_code,
//                 'name' => $this->route->route_name
//             ] : null,
//             'salesman' => $this->salesman ? [
//                 'id' => $this->salesman->id,
//                 'code' => $this->salesman->osa_code,
//                 'name' => $this->salesman->name
//             ] : null,
//             'expenceType' => $this->expenceType ? [
//                 'id' => $this->expenceType->id,
//                 'code' => $this->expenceType->osa_code,
//                 'name' => $this->expenceType->name
//             ] : null,
//             'description' => $this->description,
//             'image' => $this->image,
//             'date' => $this->date,
//             'amount' => $this->amount,
//             'status' => $this->status
//         ];
//     }
// }
namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class RouteExpenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Route_Expense')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep    = null;
        $progress       = null;

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

            $currentStep = $current?->title;
            $progress    = $totalSteps > 0 ? ($completedSteps . '/' . $totalSteps) : null;
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,

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

            'expenceType' => $this->expenceType ? [
                'id' => $this->expenceType->id,
                'code' => $this->expenceType->osa_code,
                'name' => $this->expenceType->name
            ] : null,

            'description' => $this->description,
            'image'       => $this->image,
            'date'        => $this->date,
            'amount'      => $this->amount,
            'status'      => $this->status,

            /**
             * ==========================================
             * 🚀 Approval Workflow Fields
             * ==========================================
             */
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'progress'        => $progress,
        ];
    }
}

