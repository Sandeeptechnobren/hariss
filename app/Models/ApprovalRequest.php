<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $fillable = ['uuid','workflow_id','requestable_type','requestable_id','requested_by','payload','status','current_step_id','current_step_order'];
    protected $casts = ['payload' => 'array'];
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class,'workflow_id');
    }
    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class,'requested_by');
    }
    public function stepStatuses(): HasMany
    {
        return $this->hasMany(ApprovalStepStatus::class,'request_id')->orderBy('step_order');
    }
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class,'request_id')->orderBy('action_date','asc');
    }
    public function requestable()
    {
        return $this->morphTo('requestable');
    }
}
