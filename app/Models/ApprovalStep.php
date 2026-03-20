<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStep extends Model
{
    use SoftDeletes;
    protected $fillable = ['workflow_id','step_order','approval_mode','required_count','role_id','allow_user_override','auto_approve','condition'];
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class,'workflow_id');
    }
    public function approvers(): HasMany
    {
        return $this->hasMany(Approver::class,'step_id');
    }
}
