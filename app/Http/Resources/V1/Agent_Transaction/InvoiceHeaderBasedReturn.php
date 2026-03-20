<?php

namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class InvoiceHeaderBasedReturn extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Invoice_Header')
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

            $total = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completed = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApproved = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'DESC')
                ->first();

            $approvalStatus = $lastApproved ? $lastApproved->message : 'Initiated';
            $currentStep = $current ? $current->title : null;
            $currentStepId = $current ? $current->id : null;
            $progress = $total > 0 ? ($completed . '/' . $total) : null;
        }
        $details = collect(
            InvoiceDetailBasedReturn::collection($this->whenLoaded('details'))->resolve()
        )->filter()->values();

        return [
            'header_id'       => $this->id,
            'invoice_code'    => $this->invoice_code,
            'currency_id'     => $this->currency_id ?? null,
            'currency_name'   => $this->currency_name ?? null,
            'company_id'      => $this->company_id,
            'order_number'    => $this->order_number,
            'order_code'      => $this->order->order_code ?? null,
            'delivery_number' => $this->delivery_number,
            'delivery_code'   => $this->delivery->delivery_code ?? null,

            'warehouse_id'    => $this->warehouse_id,
            'warehouse_code'  => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'  => $this->warehouse->warehouse_name ?? null,
            'warehouse_town_village' => $this->warehouse->town_village ?? null,
            'warehouse_street'   => $this->warehouse->street ?? null,
            'warehouse_landmark' => $this->warehouse->landmark ?? null,
            'warehouse_address' => $this->warehouse->address ?? null,
            'warehouse_city'     => $this->warehouse->city ?? null,
            'warehouse_tin_no'   => $this->warehouse->tin_no ?? null,
            'warehouse_contact' => $this->warehouse->warehouse_manager_contact ?? null,
            'warehouse_email'   => $this->warehouse->warehouse_email ?? null,

            'route_id'        => $this->route_id,
            'route_code'      => $this->route->route_code ?? null,
            'route_name'      => $this->route->route_name ?? null,

            'customer_id'     => $this->customer_id,
            'customer_code'   => $this->customer->osa_code ?? null,
            'customer_name'   => $this->customer->name ?? null,
            'customer_street' => $this->customer->street ?? null,
            'customer_town'   => $this->customer->town ?? null,
            'customer_landmark' => $this->customer->landmark ?? null,
            'customer_district' => $this->customer->district ?? null,
            'customer_vat' => $this->customer->vat_no ?? null,

            'salesman_id'     => $this->salesman_id,
            'salesman_code'   => $this->salesman->osa_code ?? null,
            'salesman_name'   => $this->salesman->name ?? null,

            'invoice_date'    => Carbon::parse($this->invoice_date)->format('Y-m-d'),
            'invoice_time'    => Carbon::parse($this->invoice_time)->format('H:i:s'),
            'invoice_type'    => $this->invoice_type,
            'gross_total'     => $this->gross_total,
            'vat'             => $this->vat,
            'pre_vat'         => $this->pre_vat,
            'net_total'       => $this->net_total,
            'promotion_total' => $this->promotion_total,
            'discount'        => $this->discount,
            'total_amount'    => $this->total_amount,

            'status'          => $this->status,
            'uuid'            => $this->uuid,

            // 'details'         => InvoiceDetailBasedReturn::collection($this->whenLoaded('details')),
            'details' => $details,
            'previous_uuid' => $this->previous_uuid ?? null,
            'next_uuid'     => $this->next_uuid ?? null,
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $currentStepId,
            'progress'        => $progress,
        ];
    }
}
