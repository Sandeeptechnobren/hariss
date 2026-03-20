<?php

namespace App\Services\V1\Approval_process;

use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalHistoryService
{
    public function getHistory(string $processType, int $processId): array
    {
        $workflowRequest = DB::table('htapp_workflow_requests')
            ->where('process_type', $processType)
            ->where('process_id', $processId)
            ->latest()
            ->first();

        if (!$workflowRequest) {
            throw new Exception('Approval flow does not exist.');
        }

        $steps = DB::table('htapp_workflow_request_steps')
            ->where('workflow_request_id', $workflowRequest->id)
            ->orderBy('step_order')
            ->get();

        $history = [];

        foreach ($steps as $step) {

            // $approvers = DB::table('htapp_workflow_request_step_approvers as appr')
            //     ->leftJoin('users', function ($join) {
            //         $join->on(
            //             DB::raw('CAST(users.id AS TEXT)'),
            //             '=',
            //             'appr.action_by'
            //         );
            //     })
            //     ->leftJoin('roles', function ($join) {
            //         $join->on(
            //             DB::raw('CAST(roles.id AS TEXT)'),
            //             '=',
            //             'appr.role'
            //         );
            //     })
            //     ->where('appr.request_step_id', $step->id)
            //     ->whereNotNull('appr.action')
            //     ->select(
            //         'appr.action',
            //         'appr.action_at',
            //         'appr.remarks',
            //         'users.name as user_name',
            //         'roles.name as role_name'
            //     )
            //     ->get();
        $approvers = DB::table('htapp_workflow_request_step_approvers as appr')
            ->leftJoin(
                'users',
                DB::raw('users.id'),
                '=',
                DB::raw('CAST(appr.action_by AS BIGINT)')
            )
            ->leftJoin(
                'roles',
                DB::raw('roles.id'),
                '=',
                DB::raw('CAST(appr.role AS BIGINT)')
            )
            ->where('appr.request_step_id', $step->id)
            ->whereNotNull('appr.action')
            ->select(
                'appr.action',
                'appr.action_at',
                'appr.remarks', 
                'users.name as user_name',
                'users.contact_number as contact_number',
                'roles.name as role_name'
            )
            ->get();
            $actions = [];

            foreach ($approvers as $appr) {

                $actions[] = [
                    'action'      => $appr->action,
                    'action_by'   => $appr->user_name ?? $appr->role_name,
                    'contact_number'   => $appr->contact_number,
                    'action_role' => $appr->role_name,
                    'remarks'     => $appr->remarks,
                    'action_at'   => $appr->action_at,
                ];
            }

            $history[] = [
                'step_order' => $step->step_order,
                'step_title' => $step->title,
                'status'     => $step->status,
                'actions'    => $actions
            ];
        }

        return [
            'workflow_uuid'   => $workflowRequest->uuid,
            'workflow_status' => $workflowRequest->status,
            'steps'           => $history
        ];
    }
}