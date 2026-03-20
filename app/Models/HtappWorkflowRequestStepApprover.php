<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\HtappWorkflowStepApprover;
use App\Models\HtappWorkflowRequestStepApprover;
use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;
class HtappWorkflowRequestStepApprover extends Model
{
    protected $table = 'htapp_workflow_request_step_approvers';

    protected $fillable = [
        'request_step_id',
        'user_id',
        'role', 
        'has_approved',
        'action',
        'action_by',
        'action_at',
        'remarks'
    ];
public function requestStep()
{
    return $this->belongsTo(HtappWorkflowRequestStep::class, 'request_step_id');
}
public function workflowRequest()
{
    return $this->belongsTo(HtappWorkflowRequest::class, 'workflow_request_id');
}

}
