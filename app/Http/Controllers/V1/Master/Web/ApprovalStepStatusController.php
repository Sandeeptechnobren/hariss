<?php

namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;

use App\Models\ApprovalStepStatus;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ApprovalStepStatusController extends Controller
{
    use ApiResponse;

    public function updateStatus(Request $r, $requestId, $stepId)
    {
        $r->validate([
            'status' => 'required|string'
        ]);
        $stepStatus = ApprovalStepStatus::where('request_id', $requestId)->where('step_id', $stepId)->firstOrFail();
        $stepStatus->status = $r->status;
        $stepStatus->save();
        return $this->success($stepStatus);
    }
}
