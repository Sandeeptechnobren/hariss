<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtappWorkflowRequestStep extends Model
{
    protected $table = 'htapp_workflow_request_steps';
    protected $fillable = [
        'workflow_request_id',
        'step_order',
        'title',
        'approval_type',
        'message',
        'notification',
        'status',
        'assigned_to',
        'confirmationMessage',
        'permissions',
        'returned_by',
        'return_comment',
        'is_skipped'
    ];
public function workflowRequest()
{
    return $this->belongsTo(HtappWorkflowRequest::class, 'workflow_request_id');
}

public function stepApprovers()
{
    return $this->hasMany(HtappWorkflowRequestStepApprover::class, 'request_step_id');
}

public function workflow()
{
    return $this->belongsTo(HtappWorkflow::class, 'workflow_id'); // You already copied workflow_id in snapshot
}

}
