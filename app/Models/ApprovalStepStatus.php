<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStepStatus extends Model
{
    protected $fillable = ['request_id','step_id','step_order','status','approvals_required','approvals_received','last_action_id','metadata'];
    protected $casts = ['metadata' => 'array'];
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class,'request_id');
    }
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class,'step_id');
    }
    public function lastAction(): BelongsTo
    {
        return $this->belongsTo(ApprovalAction::class,'last_action_id');
    }
}
