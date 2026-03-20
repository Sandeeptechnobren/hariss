<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\ApprovalFlow;
// use App\Models\Approvalflow;
use App\Models\Approver;
use Illuminate\Support\Facades\DB;

class ApprovalActionService
{
    public function list($filters = [])
    {
        $query = ApprovalAction::query();

        if (isset($filters['workflow_id'])) {
            $query->where('workflow_id', $filters['workflow_id']);
        }
        if (isset($filters['request_id'])) {
            $query->where('request_id', $filters['request_id']);
        }
        if (isset($filters['step_id'])) {
            $query->where('step_id', $filters['step_id']);
        }
        if (isset($filters['approver_id'])) {
            $query->where('approver_id', $filters['approver_id']);
        }
        return $query->get();
    }

    public function get($id)
    {
        return ApprovalAction::findOrFail($id);
    }

    public function create($data)
    {
        // Fill all approval action fields, add action_date
        return ApprovalAction::create(array_merge($data, [
            'action_date' => now(),
        ]));
    }

    public function update($id, $data)
    {
        $action = ApprovalAction::findOrFail($id);
        $action->update($data);
        return $action;
    }

    public function delete($id)
    {
        $action = ApprovalAction::findOrFail($id);
        return $action->delete();
    }
}
