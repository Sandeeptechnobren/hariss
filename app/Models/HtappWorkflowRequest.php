<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// class HtappWorkflowRequest extends Model
// {
//     protected $table = 'htapp_workflow_requests';

//     protected $fillable = [
//         'workflow_id',
//         'process_type',
//         'process_id',
//         'status'
//     ];
// public function steps()
// {
//     return $this->hasMany(HtappWorkflowRequestStep::class, 'workflow_request_id')
//                 ->orderBy('step_order');
// }

// public function workflow()
// {
//     return $this->belongsTo(HtappWorkflow::class, 'workflow_id');
// }

// }
class HtappWorkflowRequest extends Model
{
    protected $table = 'htapp_workflow_requests';

    protected $fillable = [
        'workflow_id',
        'process_type',
        'process_id',
        'status',
        'uuid',
    ];

    public function workflow()
    {
        return $this->belongsTo(HtappWorkflow::class, 'workflow_id');
    }

    public function getModelClassAttribute()
    {
        return config('workflow_models.' . $this->process_key);
    }

    public function getRecordAttribute()
    {
        $class = $this->model_class;
        if (!$class) {
            return null;
        }
        return $class::find($this->process_id);
    }
}
