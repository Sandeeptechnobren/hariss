<?php
namespace App\Services\V1\MasterServices\Web;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStepStatus;
use App\Models\ApprovalAction;
use App\Models\ApprovalStep;
use App\Models\Approver;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Notifications\ApprovalNeededNotification;

class ApprovalEngine
{
    public function createRequest(int $workflowId, $requestable = null, ?int $requestedBy = null, array $payload = []): ApprovalRequest
    {
        return DB::transaction(function () use ($workflowId,$requestable,$requestedBy,$payload) {
            $request = ApprovalRequest::create([
                'workflow_id' => $workflowId,
                'requestable_type' => $requestable ? $requestable::class : null,
                'requestable_id' => $requestable ? $requestable->id : null,
                'requested_by' => $requestedBy,
                'payload' => $payload,
                'status' => 'PENDING'
            ]);
            $steps = ApprovalStep::where('workflow_id',$workflowId)->orderBy('step_order')->get();
            foreach ($steps as $step) {
                $approvalsRequired = $this->calculateApprovalsRequiredForStep($step);
                $status = ApprovalStepStatus::create([
                    'request_id' => $request->id,
                    'step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'status' => $step->auto_approve ? 'AUTO_APPROVED' : 'PENDING',
                    'approvals_required' => $approvalsRequired,
                    'approvals_received' => $step->auto_approve ? $approvalsRequired : 0,
                    'metadata' => null
                ]);
                if ($step->auto_approve) {
                    $this->recordActionInternal($request,$step,$requestedBy,'AUTO_APPROVE','auto approved by rule',[]);
                }
            }
            $first = $steps->first();
            if ($first) {
                $request->current_step_id = $first->id;
                $request->current_step_order = $first->step_order;
                $request->save();
                $this->notifyApproversForStep($request,$first);
            } else {
                $request->status = 'APPROVED';
                $request->save();
            }
            return $request;
        });
    }

    // protected function calculateApprovalsRequiredForStep(ApprovalStep $step): int
    // {
    //     if ($step->required_count) {
    //         return (int)$step->required_count;
    //     }
    //     $count = Approver::where('step_id',$step->id)->count();
    //     if ($count === 0 && $step->role_id) {
    //         return 1;
    //     }
    //     if ($step->approval_mode === 'ANY') {
    //         return 1;
    //     }
    //     return max(1,$count);
    // }
    protected function calculateApprovalsRequiredForStep(ApprovalStep $step): int
    {
        if ($step->required_count) {
            return (int)$step->required_count;
        }
        // dd($step);
        $explicitUsersCount = Approver::where('step_id',$step->id)->whereNotNull('user_id')->count();
        $roleRowsCount = Approver::where('step_id',$step->id)->whereNotNull('role_id')->count();
        if ($explicitUsersCount > 0 && $roleRowsCount === 0) {
            return $step->approval_mode === 'ANY' ? 1 : max(1,$explicitUsersCount);
        }
        if ($explicitUsersCount === 0 && $roleRowsCount > 0) {
            return $step->approval_mode === 'ANY' ? 1 : 1;
        }
        if ($explicitUsersCount > 0 && $roleRowsCount > 0) {
            return $step->approval_mode === 'ANY' ? 1 : max(1,$explicitUsersCount + $roleRowsCount);
        }
        return $step->approval_mode === 'ANY' ? 1 : 1;
    }

    public function recordAction(int $requestId,int $stepId,int $approverId,string $actionType, ?string $comment = null, array $meta = [])
    {
        return DB::transaction(function () use ($requestId,$stepId,$approverId,$actionType,$comment,$meta) {
            $request = ApprovalRequest::lockForUpdate()->findOrFail($requestId);
            $step = ApprovalStep::findOrFail($stepId);
            $stepStatus = ApprovalStepStatus::where('request_id',$request->id)->where('step_id',$step->id)->lockForUpdate()->firstOrFail();
            $user = \App\Models\User::findOrFail($approverId);
            $this->authorizeApprover($request,$step,$user);
            $action = ApprovalAction::create([
                'request_id' => $request->id,
                'workflow_id' => $request->workflow_id,
                'step_id' => $step->id,
                'approver_id' => $approverId,
                'approver_snapshot' => ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'roles'=>$user->getRoleNames()->toArray()],
                'action_type' => $actionType,
                'comment' => $comment,
                'action_date' => Carbon::now(),
                'meta' => $meta
            ]);
            if ($actionType === 'APPROVE') {
                $stepStatus->approvals_received = $stepStatus->approvals_received + 1;
                $stepStatus->last_action_id = $action->id;
                if ($this->isStepSatisfied($step,$stepStatus)) {
                    $stepStatus->status = 'APPROVED';
                    $this->advanceRequestToNextStep($request,$step);
                } else {
                    $stepStatus->status = 'IN_PROGRESS';
                }
                $stepStatus->save();
            } elseif ($actionType === 'REJECT') {
                $stepStatus->status = 'REJECTED';
                $stepStatus->last_action_id = $action->id;
                $stepStatus->save();
                $request->status = 'REJECTED';
                $request->save();
            } else {
                $stepStatus->last_action_id = $action->id;
                $stepStatus->save();
            }
            return $action;
        });
    }

    // protected function authorizeApprover(ApprovalRequest $request, ApprovalStep $step, $user)
    // {
    //     $isAssigned = Approver::where('step_id',$step->id)->where('user_id',$user->id)->exists();
    //     if ($isAssigned) {
    //         return true;
    //     }
    //     if ($step->role_id) {
    //         if ($user->hasRole($step->role_id)) {
    //             return true;
    //         }
    //     }
    //     if ($step->allow_user_override && $request->requested_by == $user->id) {
    //         return true;
    //     }
    //     abort(403,'not authorized to approve');
    // }
    protected function authorizeApprover(ApprovalRequest $request, ApprovalStep $step, $user)
    {
        $isAssignedUser = Approver::where('step_id',$step->id)->where('user_id',$user->id)->exists();
        if ($isAssignedUser) return true;
        $roleRows = Approver::where('step_id',$step->id)->whereNotNull('role_id')->pluck('role_id')->filter(); 
        foreach ($roleRows as $roleName) {
            if ($user->hasRole($roleName)) return true;
        }
        if ($step->allow_user_override && $request->requested_by == $user->id) return true;
        abort(403,'not authorized to approve');
    }

    protected function isStepSatisfied(ApprovalStep $step, ApprovalStepStatus $status): bool
    {
        if ($step->required_count) {
            return $status->approvals_received >= $step->required_count;
        }
        if ($step->approval_mode === 'ANY') {
            return $status->approvals_received >= 1;
        }
        return $status->approvals_received >= $status->approvals_required;
    }

    protected function advanceRequestToNextStep(ApprovalRequest $request, ApprovalStep $currentStep)
    {
        $next = ApprovalStep::where('workflow_id',$request->workflow_id)->where('step_order','>',$currentStep->step_order)->orderBy('step_order')->first();
        if ($next) {
            $request->current_step_id = $next->id;
            $request->current_step_order = $next->step_order;
            $request->status = 'IN_PROGRESS';
            $request->save();
            $this->notifyApproversForStep($request,$next);
        } else {
            $request->current_step_id = null;
            $request->current_step_order = null;
            $request->status = 'APPROVED';
            $request->save();
        }
    }

    protected function notifyApproversForStep(ApprovalRequest $request, ApprovalStep $step)
    {
        $users = Approver::where('step_id',$step->id)->with('user')->get()->pluck('user')->filter();
        if ($users->isEmpty() && $step->role_id) {
            $users = \App\Models\User::role($step->role_id)->get();
        }
        foreach ($users as $user) {
            $user->notify((new ApprovalNeededNotification($request,$step))->delay(now()->addSeconds(1)));
        }
    }

    protected function recordActionInternal(ApprovalRequest $request, ApprovalStep $step, $approverId, $actionType, $comment = null, $meta = [])
    {
        $user = $approverId ? \App\Models\User::find($approverId) : null;
        $action = ApprovalAction::create([
            'request_id' => $request->id,
            'workflow_id' => $request->workflow_id,
            'step_id' => $step->id,
            'approver_id' => $approverId,
            'approver_snapshot' => $user ? ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'roles'=>$user->getRoleNames()->toArray()] : null,
            'action_type' => $actionType,
            'comment' => $comment,
            'action_date' => Carbon::now(),
            'meta' => $meta
        ]);
        return $action;
    }
}
