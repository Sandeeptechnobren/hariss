<?php

namespace App\Services\V1\Approval_process;

use App\Models\HtappWorkflowRequestStep;
use App\Models\HtappWorkflowRequestStepApprover;
use Illuminate\Support\Facades\DB;

class HtappWorkflowReassignService
{
    public function reassign(array $data)
    {
        return DB::transaction(function () use ($data) {

            $step = HtappWorkflowRequestStep::lockForUpdate()->findOrFail($data['request_step_id']);

            $newUserId = $data['new_user_id'];

            $step->assigned_to = $newUserId;
            $step->save();

            $approver = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)
                        ->where('user_id', $newUserId)
                        ->first();

            if (! $approver) {
                $approver = HtappWorkflowRequestStepApprover::create([
                    'request_step_id' => $step->id,
                    'user_id'         => $newUserId,
                    'role'            => null,
                    'has_approved'    => false
                ]);
            } else {
                if ($approver->has_approved) {
                    $approver->has_approved = false;
                    $approver->save();
                }
            }

            $approvers = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

            return [
                'request_step' => $step,
                'approvers'    => $approvers
            ];
        });
    }
}
