<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approver extends Model
{
    use SoftDeletes;
    protected $fillable = ['step_id','user_id','role_id','assigned_by','assigned_at','uuid'];
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class,'step_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class,'user_id');
    }
}
