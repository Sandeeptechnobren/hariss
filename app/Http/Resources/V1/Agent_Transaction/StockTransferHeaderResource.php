<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'uuid'              => $this->uuid,
            'osa_code'     => $this->osa_code,
            // 'source_warehouse' => $this->source_warehouse,
             'source_warehouse' => $this->sourceWarehouse ? [
                'id' => $this->sourceWarehouse->id,
                'code' => $this->sourceWarehouse->warehouse_code,
                'name' => $this->sourceWarehouse->warehouse_name
            ] : null,
             'destiny_warehouse' => $this->destinyWarehouse ? [
                'id' => $this->destinyWarehouse->id,
                'code' => $this->destinyWarehouse->warehouse_code,
                'name' => $this->destinyWarehouse->warehouse_name
            ] : null,
            // 'destiny_warehouse'   => $this->destiny_warehouse,
            'transfer_date'     => $this->transfer_date,
            'status'            => $this->status,
            'items' => StockTransferDetailResource::collection(
                $this->whenLoaded('details')
            ),
        ];
    }
}

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
// use App\Models\HtappWorkflowRequest;
// use App\Models\HtappWorkflowRequestStep;

// class StockTransferHeaderResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         $workflowRequest = HtappWorkflowRequest::where('process_type', 'Distributor_Stock_Transfer')
//             ->where('process_id', $this->id)
//             ->orderByDesc('id')
//             ->first();

//         $approvalStatus = null;
//         $currentStep = null;
//         $requestStepId = null;
//         $progress = null;

//         if ($workflowRequest) {

//             $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                 ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
//                 ->orderBy('step_order')
//                 ->first();

//             $lastApproved = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                 ->where('status', 'APPROVED')
//                 ->orderByDesc('step_order')
//                 ->first();

//             $totalSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
//             $completedSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                 ->where('status', 'APPROVED')
//                 ->count();

//             $approvalStatus = $lastApproved
//                 ? $lastApproved->message
//                 : 'Initiated';

//             $currentStep = $current?->title;
//             $requestStepId = $current?->id;
//             $progress = $totalSteps > 0
//                 ? "{$completedSteps}/{$totalSteps}"
//                 : null;
//         }

//         return [
//             'id'    => $this->id,
//             'uuid'  => $this->uuid,
//             'osa_code' => $this->osa_code,

//             'source_warehouse' => $this->sourceWarehouse ? [
//                 'id'   => $this->sourceWarehouse->id,
//                 'code' => $this->sourceWarehouse->warehouse_code,
//                 'name' => $this->sourceWarehouse->warehouse_name
//             ] : null,

//             'destiny_warehouse' => $this->destinyWarehouse ? [
//                 'id'   => $this->destinyWarehouse->id,
//                 'code' => $this->destinyWarehouse->warehouse_code,
//                 'name' => $this->destinyWarehouse->warehouse_name
//             ] : null,

//             'transfer_date' => $this->transfer_date,
//             'status'        => $this->status,

//             'items' => StockTransferDetailResource::collection(
//                 $this->whenLoaded('details')
//             ),

//             // ==========================================
//             // 🚀 Approval Workflow Status (STANDARD)
//             // ==========================================
//             'approval_status' => $approvalStatus,
//             'current_step'    => $currentStep,
//             'request_step_id' => $requestStepId,
//             'progress'        => $progress,
//         ];
//     }
// }
