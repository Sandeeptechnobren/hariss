<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class ReturnHeaderResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'            => $this->id,
//             'uuid'          => $this->uuid,
//             'osa_code'      => $this->osa_code,
//             'currency'      => $this->currency,

//             'country_id'    => $this->country_id,
//             'country_code'  => $this->country->country_code ?? null,
//             'country_name'  => $this->country->country_name ?? null,

//             'order_id'      => $this->order_id,
//             'order_code'    => $this->order->order_code ?? null,

//             'delivery_id'   => $this->delivery_id,
//             'delivery_code' => $this->delivery->delivery_code ?? null,

//             'warehouse_id'   => $this->warehouse_id,
//             'warehouse_code' => $this->warehouse->warehouse_code ?? null,
//             'warehouse_name' => $this->warehouse->warehouse_name ?? null,

//             'route_id'      => $this->route_id,
//             'route_code'    => $this->route->route_code ?? null,
//             'route_name'    => $this->route->route_name ?? null,

//             'customer_id'   => $this->customer_id,
//             'customer_code' => $this->customer->osa_code ?? null,
//             'customer_name' => $this->customer->name ?? null,

//             'salesman_id'   => $this->salesman_id,
//             'salesman_code' => $this->salesman->osa_code ?? null,
//             'salesman_name' => $this->salesman->name ?? null,

//             'gross_total'   => $this->gross_total,
//             'vat'           => $this->vat,
//             'net_amount'    => $this->net_amount,
//             'total'         => $this->total,
//             'discount'      => $this->discount,
//             'status'        => $this->status,
//             'created_at'    => $this->created_at,
//             'updated_at'    => $this->updated_at,

//             'details'       => ReturnDetailResource::collection($this->whenLoaded('details')),
//         ];
//     }
// }
namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class ReturnHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Return_Header')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep    = null;
        $requestStepId  = null;
        $progress       = null;

        if ($workflowRequest) {
            $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $lastApproved = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'DESC')
                ->first();

            $totalSteps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();
            $approved   = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $approvalStatus = $lastApproved
                ? $lastApproved->message
                : 'Initiated';

            $currentStep   = $current?->title;
            $requestStepId = $current?->id;
            $progress      = $totalSteps > 0 ? ($approved . '/' . $totalSteps) : null;
        }

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'osa_code'      => $this->osa_code,
            'currency'      => $this->currency,

            'country_id'    => $this->country_id,
            'country_code'  => $this->country->country_code ?? null,
            'country_name'  => $this->country->country_name ?? null,

            'order_id'      => $this->order_id,
            'order_code'    => $this->order->order_code ?? null,
            'invoice_id'   => $this->invoice->id ?? null,
            'invoice_code'   => $this->invoice->invoice_code ?? null,
            'delivery_id'   => $this->delivery_id,
            'delivery_code' => $this->delivery->delivery_code ?? null,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,

            'route_id'      => $this->route_id,
            'route_code'    => $this->route->route_code ?? null,
            'route_name'    => $this->route->route_name ?? null,

            'customer_id'   => $this->customer_id,
            'customer_code' => $this->customer->osa_code ?? null,
            'customer_name' => $this->customer->name ?? null,

            'salesman_id'   => $this->salesman_id,
            'salesman_code' => $this->salesman->osa_code ?? null,
            'salesman_name' => $this->salesman->name ?? null,

            'gross_total'   => $this->gross_total,
            'vat'           => $this->vat,
            'net_amount'    => $this->net_amount,
            'total'         => $this->total,
            'discount'      => $this->discount,
            'status'        => $this->status,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            'details'       => ReturnDetailResource::collection($this->whenLoaded('details')),

            // ==========================================
            // 🚀 Approval Workflow Status
            // ==========================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $requestStepId,
            'progress'        => $progress,
        ];
    }
}
