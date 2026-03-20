<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class ExchangeHeaderResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         return [
//             'id'              => $this->id,
//             'uuid'            => $this->uuid,
//             'exchange_code'   => $this->exchange_code,
//             'warehouse_id'    => $this->warehouse_id,
//             'warehouse_code'  => $this->warehouse->warehouse_code ?? null,
//             'warehouse_name'  => $this->warehouse->warehouse_name ?? null,
//             'customer_id'     => $this->customer_id,
//             'customer_code'   => $this->customer->osa_code ?? null,
//             'customer_name'   => $this->customer->name ?? null,
//             'comment'         => $this->comment,
//             'status'          => $this->status,
//             'invoices'        => ExchangeDetailResource::collection($this->whenLoaded('invoices')),
//             'returns'         => ExchangeDetailResource::collection($this->whenLoaded('returns')),
//             // 'currency'        => $this->currency,
//             // 'country_id'      => $this->country_id,
//             // 'country_code'    => $this->country->country_code ?? null,
//             // 'country_name'    => $this->country->country_name ?? null,
//             // 'order_id'        => $this->order_id,
//             // 'order_code'      => $this->order->order_code ?? null,
//             // 'delivery_id'     => $this->delivery_id,
//             // 'delivery_code'   => $this->delivery->delivery_code ?? null,
//             // 'route_id'        => $this->route_id,
//             // 'route_code'      => $this->route->route_code ?? null,
//             // 'route_name'      => $this->route->route_name ?? null,
//             // 'salesman_id'     => $this->salesman_id,
//             // 'salesman_code'   => $this->salesman->osa_code ?? null,
//             // 'salesman_name'   => $this->salesman->name ?? null,
//             // 'gross_total'     => $this->gross_total,
//             // 'vat'             => $this->vat,
//             // 'net_amount'      => $this->net_amount,
//             // 'total'           => $this->total,
//             // 'discount'        => $this->discount,
//         ];
//     }
// }

namespace App\Http\Resources\V1\Agent_Transaction;

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
            'uuid'           => $this->uuid,
            'exchange_code'  => $this->exchange_code,

            'warehouse_id'   => $this->warehouse_id,
            'warehouse_code' => $this->warehouse->warehouse_code ?? null,
            'warehouse_name' => $this->warehouse->warehouse_name ?? null,

            'customer_id'    => $this->customer_id,
            'customer_code'  => $this->customer->osa_code ?? null,
            'customer_name'  => $this->customer->name ?? null,

            'comment'        => $this->comment,
            'status'         => $this->status,

            'invoices'       => ExchangeDetailResource::collection($this->whenLoaded('invoices')),
            'returns'        => ExchangeDetailResource::collection($this->whenLoaded('returns')),

            // ==================================================
            // 🚀 Approval Workflow Status
            // ==================================================
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
        ];
    }
}
