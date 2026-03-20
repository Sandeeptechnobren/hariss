<?php

// namespace App\Http\Resources\V1\Agent_Transaction;

// use Illuminate\Http\Resources\Json\JsonResource;

// class AgentDeliveryHeaderResource extends JsonResource
// {
//     public function toArray($request): array
//     {
//         return [
//             'id' => $this->id,
//             'uuid' => $this->uuid,
//             'delivery_code' => $this->delivery_code,
//             'warehouse' => $this->warehouse ? [
//                 'id' => $this->warehouse->id,
//                 'code' => $this->warehouse->warehouse_code,
//                 'name' => $this->warehouse->warehouse_name,
//                 'owner_email' => $this->warehouse->owner_email,
//                 'owner_number' => $this->warehouse->owner_number,
//                 'address' => $this->warehouse->address
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
//             'customer' => $this->customer ? [
//                 'id' => $this->customer->id,
//                 'code' => $this->customer->osa_code,
//                 'name' => $this->customer->name,
//                 'email' => $this->customer->email,
//                 'contact_no' => $this->customer->contact_no,
//                 'town' => $this->customer->town,
//                 'district' => $this->customer->district,
//                 'landmark' => $this->customer->landmark
//             ] : null,
//             'country' => $this->country ? [
//                 'id' => $this->country->id,
//                 'code' => $this->country->country_code,
//                 'name' => $this->country->country_name,
//                 'currency' => $this->country->currency
//             ] : null,
//             // 'customer_id' => $this->customer_id,
//             // 'currency' => $this->currency,
//             // 'country_id' => $this->country_id,
//             // 'route_id' => $this->route_id,
//             // 'salesman_id' => $this->salesman_id,
//             'gross_total' => $this->gross_total ?? NULL,
//             'vat' => $this->vat,
//             'discount' => $this->discount,
//             'net_amount' => $this->net_amount,
//             'total' => $this->total,
//             'delivery_date' => $this->delivery_date,
//             'comment' => $this->comment,
//             'status' => $this->status,
//             'details' => AgentDeliveryDetailResource::collection($this->whenLoaded('details')),
//             'previous_uuid'=> $this->previous_uuid ?? null,
//             'next_uuid'    => $this->next_uuid ?? null,
//         ];
//     }
// }
namespace App\Http\Resources\V1\Agent_Transaction;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;

class AgentDeliveryHeaderResource extends JsonResource
{
    public function toArray($request): array
    {
        $workflowRequest = HtappWorkflowRequest::where('process_type', 'Agent_Delivery_Headers')
            ->where('process_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        $approvalStatus = null;
        $currentStep = null;
        $currentStepId = null;
        $progress = null;

        if ($workflowRequest) {
            $current = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS','RETURNED'])
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
        $statusText = match ((int) $this->status) {
            1 => 'Delivery Created',
            2 => 'Completed',
            default => 'Unknown'
        };
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'delivery_code' => $this->delivery_code,

            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->warehouse_code,
                'name' => $this->warehouse->warehouse_name,
                'owner_email' => $this->warehouse->owner_email,
                'owner_number' => $this->warehouse->owner_number,
                'address' => $this->warehouse->address
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

            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'code' => $this->customer->osa_code,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'contact_no' => $this->customer->contact_no,
                'town' => $this->customer->town,
                'district' => $this->customer->district,
                'landmark' => $this->customer->landmark
            ] : null,

            'country' => $this->country ? [
                'id' => $this->country->id,
                'code' => $this->country->country_code,
                'name' => $this->country->country_name,
                'currency' => $this->country->currency
            ] : null,

            'gross_total' => $this->gross_total ?? null,
            'vat' => $this->vat,
            'discount' => $this->discount,
            'net_amount' => $this->net_amount,
            'total' => $this->total,
            'delivery_date' => $this->created_at,
            'comment' => $this->comment,
            // 'status' => $this->status,
            'status' => $statusText,
            'details' => AgentDeliveryDetailResource::collection($this->whenLoaded('details')),

            'previous_uuid' => $this->previous_uuid ?? null,
            'next_uuid' => $this->next_uuid ?? null,

            // 🔥 Approval workflow info
            'approval_status' => $approvalStatus,
            'current_step' => $currentStep,
            'request_step_id' => $currentStepId,
            'progress' => $progress,
        ];
    }
}
