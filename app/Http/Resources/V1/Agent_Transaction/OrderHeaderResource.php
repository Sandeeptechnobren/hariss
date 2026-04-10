<?php
// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
// use App\Models\HtappWorkflowRequest;
// use App\Models\HtappWorkflowRequestStep;

// class OrderHeaderResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         $workflowRequest = HtappWorkflowRequest::where('process_type', 'order')
//             ->where('process_id', $this->id)
//             ->orderBy('id', 'DESC')
//             ->first();

//         $approvalStatus = null;
//         $currentStep = null;
//         $currentStep_id = null;
//         $progress = null;

//         if ($workflowRequest) {
//             $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                 ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
//                 ->orderBy('step_order')
//                 ->first();

//             $total = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
//             $completed = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
//                 ->where('status', 'APPROVED')
//                 ->count();

//             $approvalStatus = $workflowRequest->status;
//             $currentStep = $current->title ?? null;
//             $currentStep_id = $current->id ?? null;
//             $progress = $total > 0 ? ($completed . '/' . $total) : null;
//         }

//         return [
//             'id' => $this->id,
//             'uuid' => $this->uuid,
//             'order_code' => $this->order_code,
//             'warehouse_id' => $this->warehouse_id,
//             'warehouse_code' => $this->warehouse->warehouse_code ?? null,
//             'warehouse_name' => $this->warehouse->warehouse_name ?? null,
//             'warehouse_number' => $this->warehouse->owner_number ?? null,
//             'warehouse_address' => $this->warehouse->address ?? null,
//             'warehouse_street' => $this->warehouse->street ?? null,
//             'warehouse_town' => $this->warehouse->town_village ?? null,
//             'customer_id' => $this->customer_id,
//             'customer_code' => $this->customer->osa_code ?? null,
//             'customer_name' => $this->customer->name ?? null,
//             'customer_street' => $this->customer->street ?? null,
//             'customer_town' => $this->customer->town ?? null,
//             'customer_contact' => $this->customer->contact_no ?? null,
//             'route_id' => $this->route_id,
//             'route_code' => $this->route->route_code ?? null,
//             'route_name' => $this->route->route_name ?? null,
//             'salesman_id' => $this->route_id,
//             'salesman_code' => $this->route->osa_code ?? null,
//             'salesman_name' => $this->route->name ?? null,
//             'delivery_date' => $this->delivery_date?->format('Y-m-d'),
//             'comment' => $this->comment,
//             'status' => $this->status,
//             'created_at' => $this->created_at,
//             'details' => OrderDetailResource::collection($this->whenLoaded('details')),
//             'previous_uuid' => $this->previous_uuid ?? null,
//             'next_uuid'     => $this->next_uuid ?? null,
//             'currency' => $this->currency,
//             'order_flag' => $this->order_flag,

//             // ==========================================
//             // 🚀 Approval Workflow Status Added Here
//             // ==========================================
//             'approval_status' => $approvalStatus,
//             'current_step'    => $currentStep,
//             'request_Step_id'  =>$currentStep_id,
//             'progress'        => $progress,
//         ];
//     }
// }
namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class OrderHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'order')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep = null;
        $currentStep_id = null;
        $progress = null;

        if ($workflowRequest) {
            $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS', 'RETURNED'])
                ->orderBy('step_order')
                ->first();

            // dd($workflowRequest);
            $total = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completed = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            /**
             * ============================================================
             * 🔥 NEW REQUIRED LOGIC:
             * approval_status = message of LAST APPROVED step
             * ============================================================
             */
            $lastApprovedStep = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'desc')
                ->first();

            if ($lastApprovedStep) {
                $approvalStatus = $lastApprovedStep->message;
            } else {
                $approvalStatus = 'Initiated';
            }

            // Return current step title
            $currentStep = $current->title ?? null;
            $currentStep_id = $current->id ?? null;

            // Return progress
            $progress = $total > 0 ? ($completed . '/' . $total) : null;
        }

        $details = $this->whenLoaded('details');

        if ($request->has('is_promotional')) {
            $isPromotional = filter_var(
                $request->get('is_promotional'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if (!is_null($isPromotional)) {
                $details = $details
                    ->where('is_promotional', $isPromotional)
                    ->values();
            }
        }


        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_code' => $this->order_code,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,
            'warehouse_contact' => $this->warehouse->owner_number ?? null,
            'warehouse_address' => $this->warehouse->address ?? null,
            'warehouse_street' => $this->warehouse->street ?? null,
            'warehouse_town' => $this->warehouse->town_village ?? null,
            'customer_id' => $this->customer_id,
            'customer_code' => $this->customer->osa_code ?? null,
            'customer_name' => $this->customer->name ?? null,
            'customer_contact' => $this->customer->contact_no ?? null,
            'customer_street' => $this->customer->street ?? null,
            'customer_town' => $this->customer->town ?? null,
            'customer_contact' => $this->customer->contact_no ?? null,
            'route_id' => $this->route_id,
            'route_code' => $this->route->route_code ?? null,
            'route_name' => $this->route->route_name ?? null,
            'salesman_id' => $this->salesman_id,
            'salesman_code' => $this->salesman->osa_code ?? null,
            'salesman_name' => $this->salesman->name ?? null,
            'salesman_contact' => $this->salesman->contact_no ?? null,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'comment' => $this->comment,
            'order_flag' => match ($this->order_flag) {
                1 => 'Created Order',
                2 => 'Delivery Created',
                3 => 'Completed',
                default => 'Unknown',
            },
            'status' => $this->status,
            'created_at' => $this->created_at,
            'details' => OrderDetailResource::collection($details),
            // 'details' => OrderDetailResource::collection($this->whenLoaded('details')),
            'previous_uuid' => $this->previous_uuid ?? null,
            'next_uuid'     => $this->next_uuid ?? null,
            'currency' => $this->currency,

            // =========================================================
            // 🚀 Approval Workflow Status Added Here
            // =========================================================
            'approval_status' => $approvalStatus,      // ✔ Last approved step message
            'current_step'    => $currentStep,         // ✔ Active step title
            'request_step_id' => $currentStep_id,      // ✔ Step ID
            'progress'        => $progress,            // ✔ Completed/Total
        ];
    }
}
