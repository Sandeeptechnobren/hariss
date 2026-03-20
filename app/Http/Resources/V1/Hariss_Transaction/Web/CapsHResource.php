<?php

// namespace App\Http\Resources\V1\Hariss_Transaction\Web;

// use Illuminate\Http\Resources\Json\JsonResource;

// class CapsHResource extends JsonResource
// {
//     public function toArray($request)
//     {
//         return [
//             'id'      => $this->id,
//             'uuid'          => $this->uuid,
//             'osa_code'      => $this->osa_code,

//             'warehouse_id'       => $this->warehouse_id,
//             'warehouse_code'     => $this->warehouse->warehouse_code ?? null,
//             'warehouse_name'     => $this->warehouse->warehouse_name ?? null,
//             'warehouse_email'    => $this->warehouse->warehouse_email ?? null,

//             'driver_id'            => $this->driver_id,
//             'driver_code'          => $this->driverinfo->osa_code ?? null,
//             'driver_name'          => $this->driverinfo->driver_name ?? null,
//             'driver_contact'     => $this->driverinfo->contactno ?? null,
            
//             'truck_no'    => $this->truck_no,
//             'contact_no	' => $this->contact_no,
//             'claim_no'    => $this->claim_no,
//             'claim_date'  => $this->claim_date,
//             'claim_amount'  => $this->claim_amount,
//         ];
//     }
// }

namespace App\Http\Resources\V1\Hariss_Transaction\Web;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CapsHResource extends JsonResource
{
    public function toArray($request)
    {
        $workflowRequest = DB::table('htapp_workflow_requests')
            ->where('process_type', 'Ht_Caps_Header')
            ->where('process_id', $this->id)
            ->latest()
            ->first();

        $approvalStatus = null;
        $currentStep = null;
        $requestStepId = null;
        $progress = null;

        if ($workflowRequest) {

            $current = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->count();

            $approvedSteps = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = DB::table('htapp_workflow_request_steps')
                ->where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'desc')
                ->first();

            $approvalStatus = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $currentStep   = $current->title ?? null;
            $requestStepId = $current->id ?? null;
            $progress      = $totalSteps > 0 ? "{$approvedSteps}/{$totalSteps}" : null;
        }

        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'osa_code'        => $this->osa_code,

            'warehouse_id'    => $this->warehouse_id,
            'warehouse_code'  => $this->warehouse->warehouse_code ?? null,
            'warehouse_name'  => $this->warehouse->warehouse_name ?? null,
            'warehouse_email' => $this->warehouse->warehouse_email ?? null,

            'driver_id'       => $this->driver_id,
            'driver_code'     => $this->driverinfo->osa_code ?? null,
            'driver_name'     => $this->driverinfo->driver_name ?? null,
            'driver_contact'  => $this->driverinfo->contactno ?? null,

            'truck_no'        => $this->truck_no,
            'contact_no'      => $this->contact_no,
            'claim_no'        => $this->claim_no,
            'claim_date'      => $this->claim_date,
            'claim_amount'    => $this->claim_amount,

            // 🔥 OLD APPROVAL FORMAT (FINAL)
            'approval_status' => $approvalStatus,
            'current_step'    => $currentStep,
            'request_step_id' => $requestStepId,
            'progress'        => $progress,
        ];
    }
}
