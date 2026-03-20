<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtappWorkflowStepApprover extends Model
{
    protected $table = 'htapp_workflow_step_approvers';

    protected $fillable = [
        'workflow_step_id',
        'user_id',
        'role_id'
    ];
}
