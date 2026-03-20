<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HtappWorkflowStep extends Model
{
    protected $table = 'htapp_workflow_steps';

    protected $fillable = [
        'workflow_id',
        'step_order',
        'title',
        'approval_type',
        'message',
        'permissions',
        'confirmationMessage',
        'notification',
        'status',
        'returned_by',
        'return_comment'
    ];
}
