<?php
namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\MasterServices\Web\ApprovalEngine;
use App\Traits\ApiResponse;

class ApprovalActionController extends Controller
{
    use ApiResponse;
    protected $engine;
    public function __construct(ApprovalEngine $engine)
    {
        $this->engine = $engine;
    }
    public function approve(Request $r,$requestId,$stepId)
    {
        $action = $this->engine->recordAction($requestId,$stepId,auth()->id(),'APPROVE',$r->comment ?? null,$r->meta ?? []);
        return $this->success($action);
    }
    public function reject(Request $r,$requestId,$stepId)
    {
        $action = $this->engine->recordAction($requestId,$stepId,auth()->id(),'REJECT',$r->comment ?? null,$r->meta ?? []);
        return $this->success($action);
    }
    public function comment(Request $r,$requestId,$stepId)
    {
        $action = $this->engine->recordAction($requestId,$stepId,auth()->id(),'COMMENT',$r->comment ?? null,$r->meta ?? []);
        return $this->success($action);
    }
}
