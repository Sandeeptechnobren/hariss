<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class ApprovalHelper
{
    public static function attach(object $model, string $processType): object
    {
        $model->approval_status = 'AUTO_APPROVED';
        $model->current_step    = null;
        $model->request_step_id = null;
        $model->progress        = null;
        $model->rejected_by     = null;
        $model->rejection_reason = null;
        $workflowRequest = DB::table('htapp_workflow_requests')
            ->where('process_type', $processType)
            ->where('process_id', $model->id)
            ->latest()
            ->first();
        if (!$workflowRequest) {
            return $model;
        }
        $rejectedStep = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->where('status', 'REJECTED')
            ->orderBy('step_order', 'desc')
            ->first();
        if ($rejectedStep) {
            $rejectedByText = 'Rejected';
            $rejectedByData = null;
            $rejectedBy = DB::table('htapp_workflow_request_step_approvers')
                ->where('request_step_id', $rejectedStep->id)
                ->where('has_approved', false)
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
            if ($rejectedBy) {
                if (!empty($rejectedBy->user_id)) {
                    $user = DB::table('users')
                        ->where('id', $rejectedBy->user_id)
                        ->first();
                    if ($user) {
                        $rejectedByText = 'Rejected by ' . $user->name;
                        $rejectedByData = [
                            'type' => 'USER',
                            'id'   => $user->id,
                            'name' => $user->name,
                        ];
                    }
                } elseif (!empty($rejectedBy->role)) {
                    $role = DB::table('roles')
                        ->where('id', $rejectedBy->role)
                        ->first();
                    if ($role) {
                        $rejectedByText = 'Rejected by ' . $role->name;
                        $rejectedByData = [
                            'type' => 'ROLE',
                            'id'   => $role->id,
                            'name' => $role->name,
                        ];
                    }
                }
            }
            $reason = $rejectedStep->message ?? 'Rejected';
            $returnComment = $rejectedStep->return_comment ?? '-';
            // $model->approval_status   = $rejectedByText . ': ' . $reason;
            $model->approval_status   = $rejectedByText;
            $model->progress        = $totalSteps > 0
                ? "{$approvedSteps}/{$totalSteps}"
                : null;
            $model->current_step      = null;
            $model->request_step_id   = null;
            $model->rejected_by       = $rejectedByData;
            $model->rejection_reason  = $reason;
            $model->returnComment  = $returnComment;
            return $model;
        }
        $currentStep = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->whereIn('status', ['PENDING', 'IN_PROGRESS', 'RETURNED'])
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
        if ($workflowRequest->status === 'APPROVED') {
            $model->approval_status = 'Approved';
        } else {
            $model->approval_status = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';
        }
        $model->current_step    = $currentStep->title ?? null;
        $model->request_step_id = $currentStep->id ?? null;
        $model->progress        = $totalSteps > 0
            ? "{$approvedSteps}/{$totalSteps}"
            : null;
        return $model;
    }
}
