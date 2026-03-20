<?php
namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\MasterServices\Web\ApprovalEngine;
use App\Traits\ApiResponse;

class ApprovalRequestController extends Controller
{
    use ApiResponse;
    protected $engine;
    public function __construct(ApprovalEngine $engine)
    {
        $this->engine = $engine;
    }
    public function store(Request $r)
    {
        $requestable = null;
        if ($r->filled('requestable_type') && $r->filled('requestable_id')) {
            $model = $r->input('requestable_type');
            $requestable = $model::find($r->input('requestable_id'));
        }
        $request = $this->engine->createRequest($r->workflow_id,$requestable,auth()->id(),$r->payload ?? []);
        return $this->success($request);
    }
    public function show($id)
    {
        $req = \App\Models\ApprovalRequest::with(['stepStatuses','actions'])->findOrFail($id);
        return $this->success($req);
    }
    public function index()
    {
        $qs = \App\Models\ApprovalRequest::with('flow')->paginate(20);
        return $this->success($qs);
    }
    public function statusByEntity(Request $r)
    {
        $type = $r->input('requestable_type');
        $id = $r->input('requestable_id');
        $req = \App\Models\ApprovalRequest::with(['stepStatuses','actions','flow'])
            ->where('requestable_type',$type)
            ->where('requestable_id',$id)
            ->orderBy('created_at','desc')
            ->first();
        if (! $req) {
            return $this->fail('no approval request found',404);
        }
        return $this->success($req);
    }

}
