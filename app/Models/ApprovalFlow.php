<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalFlow extends Model
{
    protected $fillable = ['menu_id','submenu_id','workflow_name','description','is_active'];
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class,'workflow_id')->orderBy('step_order');
    }
}
