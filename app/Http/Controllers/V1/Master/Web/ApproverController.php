<?php
namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;
use App\Models\Approver;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class ApproverController extends Controller
{
    use ApiResponse;

    // public function index($stepId)
    // {
    //     $approvers = Approver::with('user')->where('step_id', $stepId)->get();
    //     return $this->success($approvers);
    // }
public function index($stepId)
{
    $approvers = Approver::with('user')->where('step_id', $stepId)->get()->map(function($a){
        return [
            'id'=>$a->id,
            'step_id'=>$a->step_id,
            'user_id'=>$a->user_id,
            'role'=>$a->role,
            'assigned_by'=>$a->assigned_by,
            'assigned_at'=>$a->assigned_at,
        ];
    });
    return $this->success($approvers);
}

    // public function store(Request $r)
    // {
    //     $approver = Approver::create([
    //         'step_id' => $r->step_id,
    //         'user_id' => $r->user_id,
    //         'assigned_by' => auth()->id(),
    //         'assigned_at' => now()
    //     ]);
    //     return $this->success($approver);
    // }
public function store(Request $r)
{
    $data = [
        'step_id' => $r->step_id,
        'user_id' => $r->user_id ?? null,
        'role_id' => $r->role_id ?? null,
        'assigned_by' => auth()->id(),
        'assigned_at' => now()
    ];
    $approver = Approver::create($data);
    return $this->success($approver);
}

    public function destroy($id)
    {
        $approver = Approver::findOrFail($id);
        $approver->delete();
        return $this->success(['deleted' => true]);
    }
}
