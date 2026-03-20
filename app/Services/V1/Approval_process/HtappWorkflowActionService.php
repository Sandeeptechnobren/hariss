<?php

namespace App\Services\V1\Approval_process;

use App\Models\HtappWorkflowRequestStep;
use App\Models\HtappWorkflowRequestStepApprover;
use App\Models\HtappWorkflowRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HtappWorkflowActionService
{
public function approve($data)
    {
        return DB::transaction(function() use ($data) {

            $user = User::find($data['approver_id']);
            $userRoleId = $user->role ?? null;

            $step = HtappWorkflowRequestStep::find($data['request_step_id']);
            if (!$step) {
                return ['message' => 'Step not found'];
            }
            $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
                ->where(function($q) use ($data, $userRoleId) {
                    $q->where('user_id', $data['approver_id']);
                    if (!is_null($userRoleId)) {
                        $q->orWhere('role', $userRoleId);
                    }
                })
                ->first();
            if (!$approver) {
                return ['message' => 'You are not assigned to approve this step'];
            }
            // $approver->has_approved = true;
            // $approver->save();
            $approver->has_approved = true;
            $approver->action       = 'APPROVED';
            $approver->action_by    = $data['approver_id'];
            $approver->action_at    = now();
            $approver->remarks      = $data['remarks'] ?? null;
            $approver->save();
            if ($approver->role_id) {
                HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
                    ->where('role', $approver->role_id)
                    // ->update([
                    //     'has_approved' => true
                    // ]);
                    ->update([
                        'has_approved' => true,
                        'action'        => 'APPROVED',
                        'action_by'     => $data['approver_id'],
                        'action_at'     => now(),
                    ]);
            }
            $allApprovers = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();
            $allApproved = $allApprovers->every(fn($appr) => $appr->has_approved);
            $oneApproved = $allApprovers->contains(fn($appr) => $appr->has_approved);
            $conditionSatisfied = false;
            if ($step->approval_type == 'OR' && $oneApproved) {
                $conditionSatisfied = true;
            }
            if ($step->approval_type == 'AND' && $allApproved) {
                $conditionSatisfied = true;
            }
            if ($conditionSatisfied) {
                $step->status = 'APPROVED';
                $step->save();
                $this->moveToNextStep($step->workflow_request_id, $step->step_order);
            }
            return [
                'step_status' => $step->status,
                'approval_type' => $step->approval_type,
                'condition_satisfied' => $conditionSatisfied
            ];
        });
    }

public function editbeforeapproval($data)
{
    return DB::transaction(function() use ($data) {
        $step = HtappWorkflowRequestStep::find($data['request_step_id']);
        $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
                    ->where('user_id', $data['approver_id'])
                    ->first();
        if (! $approver) {
            return ['message' => 'Approver not assigned to this step'];
        }
        // $approver->has_approved = true;
        // $approver->save();
        $approver->update([
            'has_approved' => true,
            'action'        => 'APPROVED',
            'action_by'     => $data['approver_id'],
            'action_at'     => now(),
            'remarks'       => $data['comment'] ?? null,
        ]);
        $allApprovers = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();
        $allApproved = $allApprovers->every(fn($appr) => $appr->has_approved);
        $oneApproved = $allApprovers->contains(fn($appr) => $appr->has_approved);
        $conditionSatisfied = false;
        if ($step->approval_type == 'OR' && $oneApproved) {
            $conditionSatisfied = true;
        }
        if ($step->approval_type == 'AND' && $allApproved) {
            $conditionSatisfied = true;
        }
        if ($conditionSatisfied) {
            $step->status = 'APPROVED';
            $step->save();

            $this->moveToNextStep($step->workflow_request_id, $step->step_order);
        }
        return ['step_status' => $step->status];
    });
}
private function moveToNextStep($workflowRequestId, $currentStepOrder)
{
    $nextStep = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequestId)->where('step_order', $currentStepOrder + 1)->first();
    if (! $nextStep) {
        $request = HtappWorkflowRequest::find($workflowRequestId);
        $request->status = 'APPROVED';
        $request->save();
        return true;
    }
    $request = HtappWorkflowRequest::find($workflowRequestId);
    $request->status = 'IN_PROGRESS';
    $request->save();

    return true;
}

// public function reject($data)
//     {
//         return DB::transaction(function() use ($data) {
//             $step = HtappWorkflowRequestStep::find($data['request_step_id']);
//             $step->status = 'REJECTED';
//             $step->save();
//             $workflowRequest = HtappWorkflowRequest::find($step->workflow_request_id);
//             $workflowRequest->status = 'REJECTED';
//             $workflowRequest->save();
//             return [
//                 'step_status' => 'REJECTED',
//                 'workflow_status' => 'REJECTED'
//             ];
//         });
//     }
public function reject(array $data)
{
    return DB::transaction(function () use ($data) {

        $step = HtappWorkflowRequestStep::findOrFail($data['request_step_id']);

        $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
            ->where(function ($q) use ($data) {
                $q->where('user_id', $data['approver_id'])
                  ->orWhere('role', auth()->user()->role);
            })
            ->first();

        if (!$approver) {
            throw new \Exception('You are not allowed to reject this step.');
        }

        // $approver->has_approved = false;
        // $approver->save();
        $approver->has_approved = false;
        $approver->action       = 'REJECTED';
        $approver->action_by    = $data['approver_id'];
        $approver->action_at    = now();
        $approver->remarks      = $data['remarks'] ?? 'Rejected';
        $approver->save();
        // $approver->update([
        //     'has_approved' => false,
        //     'action'        => 'REJECTED',
        //     'action_by'     => $data['approver_id'],
        //     'action_at'     => now(),
        //     'remarks'       => $data['reason'] ?? 'Rejected',
        // ]);
        $step->status = 'REJECTED';
        $step->save();

        $request = HtappWorkflowRequest::find($step->workflow_request_id);
        $request->status = 'REJECTED';
        $request->save();

        return [
            'status' => 'REJECTED',
            'message' => 'Approval request rejected successfully'
        ];
    });
}

// public function returnBack($data)
// {
//     return DB::transaction(function() use ($data) {
//         $step = HtappWorkflowRequestStep::find($data['request_step_id']);
//         $step->status = 'RETURNED';
//         $step->save();
//         $prevStep = HtappWorkflowRequestStep::where('workflow_request_id', $step->workflow_request_id)
//                         ->where('step_order', '<', $step->step_order)
//                         ->orderBy('step_order', 'DESC')
//                         ->first();
//         if (! $prevStep) {
//             return ['message' => 'No previous step exists'];
//         }
//         $prevStep->status = 'PENDING';
//         $prevStep->save();
//         HtappWorkflowRequestStepApprover::where('request_step_id', $prevStep->id)
//             ->update(['has_approved' => false]);
//         $workflowRequest = HtappWorkflowRequest::find($step->workflow_request_id);
//         $workflowRequest->status = 'IN_PROGRESS';
//         $workflowRequest->save();

//         return [
//             'success' => true,
//             'current_step' => $step->id,
//             'previous_step' => $prevStep->id,
//             'message' => 'Workflow returned to previous step'
//         ];
//     });
// }
// public function returnBack($data)
// {
//     return DB::transaction(function() use ($data) {
//         $step = HtappWorkflowRequestStep::find($data['request_step_id']);
//         $step->status = 'RETURNED';
//         $step->returned_by = $data['approver_id'];
//         $step->return_comment = $data['comment'] ?? null;
//         $step->save();
//         $prevStep = HtappWorkflowRequestStep::where('workflow_request_id', $step->workflow_request_id)
//                         ->where('step_order', '<', $step->step_order)
//                         ->orderBy('step_order', 'DESC')
//                         ->first();
//         if (! $prevStep) {
//             return ['message' => 'No previous step exists for return-back'];
//         }
//         $prevStep->status = 'PENDING';
//         $prevStep->is_skipped = false;
//         $prevStep->save();
//         HtappWorkflowRequestStepApprover::where('request_step_id', $prevStep->id)
//             ->update(['has_approved' => false]);
//         $workflowRequest = HtappWorkflowRequest::find($step->workflow_request_id);
//         $workflowRequest->status = 'IN_PROGRESS';
//         $workflowRequest->save();
//         return [
//             'success' => true,
//             'message' => 'Workflow returned to previous step',
//             'returned_from_step' => $step->id,
//             'activated_step' => $prevStep->id
//         ];
//     });
// }
public function returnBack(array $data)
{
    return DB::transaction(function () use ($data) {
        $userId = $data['approver_id'];
        $step = HtappWorkflowRequestStep::findOrFail($data['request_step_id']);
        $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('role', auth()->user()->role);
            })
            ->first();
        if (!$approver) {
            throw new \Exception('You are not allowed to return this step.');
        }
        $step->update([
            'status'         => 'RETURNED',
            'returned_by'    => $userId,
            'return_comment' => $data['comment'] ?? null,
        ]);
        // $approver->update([
        //     'action'    => 'RETURNED',
        //     'action_by' => $userId,
        //     'action_at' => now(),
        //     'remarks'   => $data['comment'] ?? null,
        // ]);
        $approver->action     = 'RETURNED';
        $approver->action_by  = $data['approver_id'];
        $approver->action_at  = now();
        $approver->remarks    = $data['comment'] ?? null;
        $approver->save();
        $previousStep = HtappWorkflowRequestStep::where('workflow_request_id', $step->workflow_request_id)
            ->where('step_order', '<', $step->step_order)
            ->orderBy('step_order', 'DESC')
            ->first();
        if (!$previousStep) {
            return [
                'success' => false,
                'message' => 'No previous step exists for return back.'
            ];
        }
        $previousStep->update([
            'status'     => 'PENDING',
            'is_skipped' => false
        ]);
        HtappWorkflowRequestStepApprover::where('request_step_id', $previousStep->id)
            ->update([
                'has_approved' => false,
                'action'        => null,
                'action_by'     => null,
                'action_at'     => null,
                'remarks'       => null,
            ]);
        HtappWorkflowRequest::where('id', $step->workflow_request_id)
            ->update([
                'status' => 'IN_PROGRESS'
            ]);
        return [
            'success' => true,
            'message' => 'Workflow returned to previous step successfully.',
            'returned_from_step' => $step->id,
            'activated_step'     => $previousStep->id
        ];
    });
}
public function getMyPermissions($processType, $processId, $userId, $roleId)
{
    $workflowRequest = HtappWorkflowRequest::where('process_type', $processType)
        ->where('process_id', $processId)
        ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
        ->orderBy('id', 'DESC')
        ->first();
    if (!$workflowRequest) {
        return [
            'permissions' => [],
            'message' => 'No active workflow found for this request'
        ];
    }
    $step = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
        ->where('is_skipped', false)
        ->where('status', 'PENDING')
        ->orderBy('step_order')
        ->first();
    if (!$step) {
        return [
            'permissions' => [],
            'message' => 'No active approval step'
        ];
    }
    $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
        ->where(function($q) use ($userId, $roleId) {
            $q->where('user_id', $userId);
            if ($roleId !== null) {
                $q->orWhere('role', $roleId);
            }
        })
        ->first();

    if (!$approver) {
        return [
            'permissions' => [],
            'message' => 'You are not assigned to approve this step'
        ];
    }

    $permissions = json_decode($step->permissions, true) ?? [];

    return [
        'workflow_request_uuid' => $workflowRequest->uuid,
        'step_uuid' => $step->uuid,
        'step_order' => $step->step_order,
        'step_title' => $step->title,
        'permissions' => $permissions
    ];
}


}
