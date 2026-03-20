<?php

namespace App\Services\V1\Approval_process;

use App\Models\HtappWorkflow;
use App\Models\HtappWorkflowStep;
use App\Models\HtappWorkflowStepApprover;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;
use App\Models\HtappWorkflowRequestStepApprover;

use Illuminate\Support\Facades\DB;

class HtappWorkflowApprovalService
{

// public function startApproval($data)
// {
//     return DB::transaction(function () use ($data) {

//         $processType = $data['process_type'];
//         $modelClass  = config("workflow_models.$processType");

//         if (!$modelClass) {
//             return [
//                 'success' => false,
//                 'message' => 'Invalid process type. Check workflow_models config.'
//             ];
//         }

//         $modelRecord = $modelClass::find($data['process_id']);
//         if (!$modelRecord) {
//             return [
//                 'success' => false,
//                 'message' => "Record not found for process: {$processType}"
//             ];
//         }

//         $existing = HtappWorkflowRequest::where('process_type', $processType)
//             ->where('process_id', $data['process_id'])
//             ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
//             ->first();

//         if ($existing) {
//             return [
//                 'success' => false,
//                 'message' => 'An approval is already in progress for this process.',
//                 'workflow_request_uuid' => $existing->uuid
//             ];
//         }

//         $workflowRequest = HtappWorkflowRequest::create([
//             'workflow_id'  => $data['workflow_id'],
//             'process_type' => $processType,
//             'process_id'   => $data['process_id'],
//             'status'       => 'PENDING'
//         ]);

//         $steps = HtappWorkflowStep::where('workflow_id', $data['workflow_id'])
//             ->orderBy('step_order')
//             ->get();

//         $loggedUser = auth()->user();
//         $isSuperAdmin = $loggedUser && $loggedUser->role == 1;

//         foreach ($steps as $step) {

//             $reqStep = HtappWorkflowRequestStep::create([
//                 'workflow_request_id' => $workflowRequest->id,
//                 'step_order'          => $step->step_order,
//                 'title'               => $step->title,
//                 'approval_type'       => $step->approval_type,
//                 'message'             => $step->message,
//                 'notification'        => $step->notification,
//                 'permissions'         => $step->permissions,
//                 'confirmationMessage' => $step->confirmationMessage,
//                 'status'              => $isSuperAdmin ? 'APPROVED' : 'PENDING'
//             ]);

//             $approvers = HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->get();

//             foreach ($approvers as $appr) {
//                 HtappWorkflowRequestStepApprover::create([
//                     'request_step_id' => $reqStep->id,
//                     'user_id'         => $appr->user_id,
//                     'role'            => $appr->role_id,
//                     'has_approved'    => $isSuperAdmin ? true : false
//                 ]);
//             }
//         }

//         if ($isSuperAdmin) {
//             $workflowRequest->status = 'APPROVED';
//             $workflowRequest->save();

//             return [
//                 'success' => true,
//                 'workflow_request_uuid' => $workflowRequest->uuid,
//                 'workflow_status' => 'APPROVED',
//                 'auto_approved' => true
//             ];
//         }

//         return [
//             'success' => true,
//             'workflow_request_uuid' => $workflowRequest->uuid,
//             'workflow_status' => $workflowRequest->status
//         ];
//     });
// }
// public function startApproval($data)
// {
//     return DB::transaction(function () use ($data) {

//         $processType = $data['process_type'];
//         $processId   = $data['process_id'];
//         $user        = auth()->user();

//         $assignment = DB::table('htapp_workflow_assignments')
//             ->where('process_type', $processType)
//             ->where('workflow_id', $data['workflow_id'])
//             ->first();

//         if (!$assignment || !$assignment->is_active) {
//             return [
//                 'success' => true,
//                 'workflow_status' => 'AUTO_APPROVED',
//                 'message' => 'Approval flow disabled. Process auto-approved.'
//             ];
//         }

//         $modelClass = config("workflow_models.$processType");
//         if (!$modelClass || !$modelClass::find($processId)) {
//             return [
//                 'success' => false,
//                 'message' => 'Invalid process or process not found'
//             ];
//         }

//         $existing = HtappWorkflowRequest::where('process_type', $processType)
//             ->where('process_id', $processId)
//             ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
//             ->first();

//         if ($existing) {
//             return [
//                 'success' => false,
//                 'workflow_request_uuid' => $existing->uuid,
//                 'message' => 'Approval already in progress'
//             ];
//         }

//         $workflowRequest = HtappWorkflowRequest::create([
//             'workflow_id'  => $data['workflow_id'],
//             'process_type' => $processType,
//             'process_id'   => $processId,
//             'status'       => 'PENDING'
//         ]);

//         $steps = HtappWorkflowStep::where('workflow_id', $data['workflow_id'])
//             ->orderBy('step_order')
//             ->get();

//         $autoApproveAll = ($user && (int)$user->role === 1);

//         foreach ($steps as $step) {

//             $stepStatus = $autoApproveAll ? 'APPROVED' : 'PENDING';

//             $reqStep = HtappWorkflowRequestStep::create([
//                 'workflow_request_id' => $workflowRequest->id,
//                 'step_order'          => $step->step_order,
//                 'title'               => $step->title,
//                 'approval_type'       => $step->approval_type,
//                 'message'             => $step->message,
//                 'notification'        => $step->notification,
//                 'permissions'         => $step->permissions,
//                 'status'              => $stepStatus
//             ]);

//             $approvers = HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->get();

//             foreach ($approvers as $appr) {
//                 HtappWorkflowRequestStepApprover::create([
//                     'request_step_id' => $reqStep->id,
//                     'user_id'         => $appr->user_id,
//                     'role'            => $appr->role_id,
//                     'has_approved'    => $autoApproveAll ? true : false
//                 ]);
//             }
//         }

//         if ($autoApproveAll) {
//             $workflowRequest->update(['status' => 'APPROVED']);
//         }

//         return [
//             'success' => true,
//             'workflow_request_uuid' => $workflowRequest->uuid,
//             'workflow_status' => $workflowRequest->status
//         ];
//     });
// }

public function startApproval(array $data)
{
    return DB::transaction(function () use ($data) {

        $processType = $data['process_type'];
        $processId   = $data['process_id'];
        $user        = auth()->user();
        $assignment = DB::table('htapp_workflow_assignments')
            ->where('process_type', $processType)
            ->where('is_active', true)
            ->first();

        if (!$assignment) {
            return [
                'success'          => true,
                'workflow_status'  => 'AUTO_APPROVED',
                'approval_applied' => false,
                'message'          => 'Approval flow disabled. Process auto-approved.'
            ];
        }

        $workflowId = $assignment->workflow_id;
        $modelClass = config("workflow_models.$processType");

        if (!$modelClass || !$modelClass::find($processId)) {
            return [
                'success' => false,
                'message' => 'Invalid process type or process record not found.'
            ];
        }
        $existing = HtappWorkflowRequest::where('process_type', $processType)
            ->where('process_id', $processId)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
            ->first();

        if ($existing) {
            return [
                'success'                => false,
                'workflow_request_uuid'  => $existing->uuid,
                'workflow_status'        => $existing->status,
                'message'                => 'Approval already in progress.'
            ];
        }
        $workflowRequest = HtappWorkflowRequest::create([
            'workflow_id'  => $workflowId,
            'process_type' => $processType,
            'process_id'   => $processId,
            'status'       => 'PENDING'
        ]);
        $autoApproveAll = ($user && (int) $user->role === 1);
        $steps = HtappWorkflowStep::where('workflow_id', $workflowId)
            ->orderBy('step_order')
            ->get();

        foreach ($steps as $step) {

            $stepStatus = $autoApproveAll ? 'APPROVED' : 'PENDING';

            $requestStep = HtappWorkflowRequestStep::create([
                'workflow_request_id' => $workflowRequest->id,
                'step_order'          => $step->step_order,
                'title'               => $step->title,
                'approval_type'       => $step->approval_type,
                'message'             => $step->message,
                'notification'        => $step->notification,
                'permissions'         => $step->permissions,
                'confirmationMessage' => $step->confirmationMessage ?? null,
                'status'              => $stepStatus
            ]);

            $approvers = HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->get();

            foreach ($approvers as $appr) {
                HtappWorkflowRequestStepApprover::create([
                    'request_step_id' => $requestStep->id,
                    'user_id'         => $appr->user_id,
                    'role'            => $appr->role_id,
                    'has_approved'    => $autoApproveAll ? true : false
                ]);
            }
        }

        if ($autoApproveAll) {
            $workflowRequest->update(['status' => 'APPROVED']);
        }
        return [
            'success'                => true,
            'approval_applied'       => true,
            'workflow_request_uuid'  => $workflowRequest->uuid,
            'workflow_status'        => $workflowRequest->status,
            'auto_approved'          => $autoApproveAll
        ];
    });
}


}
