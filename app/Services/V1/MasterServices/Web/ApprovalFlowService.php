<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\ApprovalFlow;
// use App\Models\Approvalflow;
use App\Models\ApprovalAction;
use Illuminate\Support\Facades\DB;

class ApprovalFlowService
{
    public function list($filters = [])
    {
        return ApprovalFlow::with('steps')->get();
    }

    public function show($id)
    {

        return ApprovalWorkflow::with('steps')->findOrFail($id);
    }

    public function create($data)
    {
        return DB::transaction(function () use ($data) {

            $workflow = Approvalflow::create([
                'menu_id'       => $data['menu_id'],
                'submenu_id'    => $data['submenu_id'] ?? null,
                'workflow_name' => $data['workflow_name'],
                'description'   => $data['description'] ?? null,
                'is_active'     => $data['is_active'] ?? true,
            ]);

            foreach ($data['steps'] as $step) {
                $workflow->steps()->create($step);
            }

            return $workflow->load('steps');
        });
    }

public function update($id, $data)
{
    return DB::transaction(function () use ($id, $data) {
        $workflow = ApprovalFlow::findOrFail($id);
        $workflow->update([
            'workflow_name' => $data['workflow_name'] ?? $workflow->workflow_name,
            'description'   => $data['description'] ?? $workflow->description,
            'is_active'     => $data['is_active'] ?? $workflow->is_active,
        ]);

        // 1. Track processed step IDs to delete steps later
        $processedStepIds = [];

        // 2. Loop through payload steps and match by workflow_id and user_id
        foreach ($data['steps'] as $stepData) {
            $existingStep = $workflow->steps()
                ->where('user_id', $stepData['user_id'])
                ->where('role_id',$stepData['role_id'])
                ->where('workflow_id', $workflow->id)
                ->first();

            if ($existingStep) {
                $existingStep->update($stepData);
                $processedStepIds[] = $existingStep->id;
            } else {
                $newStep = $workflow->steps()->create($stepData);
                $processedStepIds[] = $newStep->id;
            }
        }

        // 3. Optionally, delete any steps not present in processedStepIds
        $workflow->steps()
            ->whereNotIn('id', $processedStepIds)
            ->delete();

        return $workflow->load('steps');
    });
}

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {

            $workflow = ApprovalWorkflow::findOrFail($id);

            $workflow->steps()->delete();
            $workflow->delete();

            return true;
        });
    }

    public function startApproval($workflowId, $requestId)
    {
        $workflow = ApprovalWorkflow::findOrFail($workflowId);

        $steps = $workflow->steps()->orderBy('step_order')->get();

        foreach ($steps as $step) {
            ApprovalAction::create([
                'workflow_id' => $workflowId,
                'step_id'     => $step->id,
                'request_id'  => $requestId,
                'status'      => 'pending',
            ]);
        }
    }

    public function approveStep($actionId, $approverId, $actionType, $comment = null)
    {
        $action = ApprovalAction::findOrFail($actionId);

        $action->update([
            'action_type' => $actionType,
            'comment'     => $comment,
            'approver_id' => $approverId,
            'action_date' => now(),
            'status'      => 'completed',
        ]);

        // Optional: implement sequential logic here
    }
}
