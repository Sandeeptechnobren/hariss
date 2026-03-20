<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Models\ApprovalStep;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ApprovalStepController extends Controller
{
    use ApiResponse;
public function index($flowId)
    {
        $steps = ApprovalStep::where('workflow_id', $flowId)->orderBy('step_order')->get();
        return $this->success($steps);
    }
public function store(Request $r)
    {
        $step = ApprovalStep::create([
            'workflow_id' => $r->workflow_id,
            'step_order' => $r->step_order,
            'approval_mode' => $r->approval_mode,
            'required_count' => $r->required_count,
            'role_id' => $r->role_id,
            'allow_user_override' => $r->allow_user_override,
            'auto_approve' => $r->auto_approve,
            'condition' => $r->condition
        ]);
        return $this->success($step);
    }
public function update(Request $r, $id)
    {
        $step = ApprovalStep::findOrFail($id);
        $step->update($r->all());
        return $this->success($step);
    }
public function destroy($id)
    {
        $step = ApprovalStep::findOrFail($id);
        $step->delete();
        return $this->success(['deleted' => true]);
    }
}
