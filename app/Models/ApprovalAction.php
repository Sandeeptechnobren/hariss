<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $fillable = ['request_id','workflow_id','step_id','approver_id','approver_snapshot','action_type','comment','action_date','meta'];
    protected $casts = ['approver_snapshot' => 'array','meta' => 'array','action_date'=>'datetime'];
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class,'request_id');
    }
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class,'step_id');
    }
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class,'approver_id');
    }
}
