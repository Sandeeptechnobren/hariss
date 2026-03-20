<?php

namespace App\Http\Controllers\V1\Master\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApprovalFlow;
use App\Traits\ApiResponse;

class ApprovalFlowController extends Controller
{
    use ApiResponse;
    public function index()
    {
        $flows = ApprovalFlow::with('steps')->get();
        return $this->success($flows);
    }
    public function show($id)
    {
        $flow = ApprovalFlow::with('steps.approvers')->findOrFail($id);
        return $this->success($flow);
    }
    public function store(Request $r)
    {
        $flow = ApprovalFlow::create($r->only(['menu_id','submenu_id','workflow_name','description','is_active']));
        return $this->success($flow);
    }
    public function update(Request $r,$id)
    {
        $flow = ApprovalFlow::findOrFail($id);
        $flow->update($r->only(['menu_id','submenu_id','workflow_name','description','is_active']));
        return $this->success($flow);
    }
    public function reorderSteps(Request $r,$id)
    {
        $orders = $r->input('orders',[]);
        foreach ($orders as $stepId => $order) {
            \App\Models\ApprovalStep::where('id',$stepId)->update(['step_order'=>$order]);
        }
        return $this->success(['message'=>'reordered']);
    }
}

// namespace App\Http\Controllers\V1\Master\Web;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Services\V1\MasterServices\Web\ApprovalFlowService;
// use App\Http\Requests\V1\MasterRequests\Web\ApprovalFlowRequest;
// use App\Http\Requests\V1\MasterRequests\Web\UpdateApprovalWorkflowRequest;

// // class ApprovalFlowController extends Controller
// // {
// //     protected $service;

// //     public function __construct(ApprovalFlowService $service)
// //     {
// //         $this->service = $service;
// //     }

// //     public function index()
// //     {
// //         $workflows = $this->service->list();
// //         return response()->json($workflows);
// //     }

// //     public function show($id)
// //     {
// //         $workflow = $this->service->show($id);
// //         return response()->json($workflow);
// //     }

// //     public function store(ApprovalFlowRequest $request)
// //     {
// //         $workflow = $this->service->create($request->validated());
// //         return response()->json($workflow, 201);
// //     }

// //     public function update(UpdateApprovalWorkflowRequest $request, $id)
// //     {
// //         $workflow = $this->service->update($id, $request->validated());
// //         return response()->json($workflow);
// //     }

// //     public function destroy($id)
// //     {
// //         $deleted = $this->service->delete($id);
// //         return response()->json(['deleted' => $deleted]);
// //     }

// //     public function initiateApproval(Request $request)
// //     {
// //         $workflowId = $request->input('workflow_id');
// //         $requestId = $request->input('request_id');

// //         $this->service->startApproval($workflowId, $requestId);

// //         return response()->json(['message' => 'Approval initiated']);
// //     }

// //     public function takeAction(Request $request, $actionId)
// //     {
// //         $approverId = $request->user()->id;
// //         $actionType = $request->input('action_type');
// //         $comment = $request->input('comment', null);

// //         $this->service->approveStep($actionId, $approverId, $actionType, $comment);

// //         return response()->json(['message' => 'Action taken']);
// //     }
// // }
// /**
//  * @OA\Tag(
//  *     name="Approval Flows",
//  *     description="API for managing dynamic approval flows"
//  * )
//  */

// class ApprovalFlowController extends Controller
// {
//     protected $service;

//     public function __construct(ApprovalFlowService $service)
//     {
//         $this->service = $service;
//     }

//     /**
//      * @OA\Get(
//      *     path="/api/v1/master/approval/index",
//      *     summary="List all approval flows",
//      *     tags={"Approval Flows"},
//      *     @OA\Response(response=200, description="List of approval flows")
//      * )
//      */
//     public function index() {
//         $workflows = $this->service->list();
//         return response()->json($workflows);
//     }

//     /**
//      * @OA\Get(
//      *     path="/api/v1/master/web/approval/{id}",
//      *     summary="Get a single approval flow",
//      *     tags={"Approval Flows"},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         required=true,
//      *         description="Approval flow ID",
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\Response(response=200, description="Single approval flow object")
//      * )
//      */
//     public function show($id) {
//         $workflow = $this->service->show($id);
//         return response()->json($workflow);
//     }

//     /**
//      * @OA\Post(
//      *     path="/api/v1/master/web/approval/store",
//      *     summary="Create a new approval flow",
//      *     tags={"Approval Flows"},
//      *     @OA\RequestBody(
//      *         required=true,
//      *     ),
//      *     @OA\Response(response=201, description="Approval flow created")
//      * )
//      */
//     public function store(ApprovalFlowRequest $request) {
//         $workflow = $this->service->create($request->validated());
//         return response()->json($workflow, 201);
//     }
//     /**
//      * @OA\Put(
//      *     path="/api/v1/master/web/approval/{id}",
//      *     summary="Update an approval flow",
//      *     tags={"Approval Flows"},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         required=true,
//      *         description="Approval flow ID",
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\RequestBody(
//      *         required=true,
//      *     ),
//      *     @OA\Response(response=200, description="Approval flow updated")
//      * )
//      */
//     public function update(UpdateApprovalWorkflowRequest $request, $id) {
//         $workflow = $this->service->update($id, $request->validated());
//         return response()->json($workflow);
//     }

//     /**
//      * @OA\Delete(
//      *     path="/api/v1/master/web/approval/{id}",
//      *     summary="Delete an approval flow",
//      *     tags={"Approval Flows"},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         required=true,
//      *         description="Approval flow ID",
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\Response(response=200, description="Approval flow deleted")
//      * )
//      */
//     public function destroy($id) {
//         $deleted = $this->service->delete($id);
//         return response()->json(['deleted' => $deleted]);
//     }

//     /**
//      * @OA\Post(
//      *     path="/api/v1/master/web/approval/initiate-approval",
//      *     summary="Initiate approval process for a request",
//      *     tags={"Approval Flows"},
//      *     @OA\RequestBody(
//      *         required=true,
//      *         @OA\JsonContent(
//      *             required={"workflow_id", "request_id"},
//      *             @OA\Property(property="workflow_id", type="integer"),
//      *             @OA\Property(property="request_id", type="integer")
//      *         )
//      *     ),
//      *     @OA\Response(response=200, description="Approval initiated")
//      * )
//      */
//     public function initiateApproval(Request $request) {
//         $workflowId = $request->input('workflow_id');
//         $requestId = $request->input('request_id');
//         $this->service->startApproval($workflowId, $requestId);
//         return response()->json(['message' => 'Approval initiated']);
//     }

//     /**
//      * @OA\Post(
//      *     path="/api/v1/master/web/approval-flows/take-action/{actionId}",
//      *     summary="Take approval action (approve, reject, etc)",
//      *     tags={"Approval Flows"},
//      *     @OA\Parameter(
//      *         name="actionId",
//      *         in="path",
//      *         required=true,
//      *         description="Approval action ID",
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\RequestBody(
//      *         required=true,
//      *         @OA\JsonContent(
//      *             required={"action_type"},
//      *             @OA\Property(property="action_type", type="string", example="approved"),
//      *             @OA\Property(property="comment", type="string", example="Looks good")
//      *         )
//      *     ),
//      *     @OA\Response(response=200, description="Action taken successfully")
//      * )
//      */
//     public function takeAction(Request $request, $actionId) {
//         $approverId = $request->user()->id;
//         $actionType = $request->input('action_type');
//         $comment = $request->input('comment', null);
//         $this->service->approveStep($actionId, $approverId, $actionType, $comment);
//         return response()->json(['message' => 'Action taken']);
//     }
// }
