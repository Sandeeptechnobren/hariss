<?php
namespace App\Http\Controllers\V1\Approval_process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\Approval_process\HtappWorkflowActionService;

/**
 * @OA\Tag(
 *     name="Approval Actions",
 *     description="Approve, Reject & Return Back actions for workflow"
 * )
 */
class HtappWorkflowActionController extends Controller
{
    protected $service;

    public function __construct(HtappWorkflowActionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/approve",
     *     summary="Approve a workflow step",
     *     tags={"Approval Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"request_step_id","approver_id"},
     *           @OA\Property(property="request_step_id", type="integer", example=112),
     *           @OA\Property(property="approver_id", type="integer", example=44)
     *        )
     *     ),
     *     @OA\Response(response=200, description="Step approved successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function approve(Request $request)
    {
        $request->validate([
            'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
            'approver_id'     => 'required|integer'
        ]);
        $result = $this->service->approve($request->all());
        return response()->json(['success' => true, 'data' => $result]);
    }

        /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/editbefore-approval",
     *     summary="Approve a workflow step",
     *     tags={"Approval Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"request_step_id","approver_id"},
     *           @OA\Property(property="request_step_id", type="integer", example=112),
     *           @OA\Property(property="approver_id", type="integer", example=44)
     *        )
     *     ),
     *     @OA\Response(response=200, description="Step approved successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function editbeforeapproval(Request $request)
    {
        $request->validate([
            'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
            'approver_id'     => 'required|integer'
        ]);

        $result = $this->service->editbeforeapproval($request->all());
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/reject",
     *     summary="Reject a workflow step",
     *     tags={"Approval Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"request_step_id","approver_id"},
     *           @OA\Property(property="request_step_id", type="integer", example=112),
     *           @OA\Property(property="approver_id", type="integer", example=44),
     *           @OA\Property(property="comment", type="string", example="Not valid pricing")
     *        )
     *     ),
     *     @OA\Response(response=200, description="Step rejected successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function reject(Request $request)
    {
        $request->validate([
            'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
            'approver_id'     => 'required|integer'
        ]);

        $result = $this->service->reject($request->all());
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/return-back",
     *     summary="Return workflow step to previous step with comments",
     *     description="If step supports RETURN_BACK permission workflow moves back",
     *     tags={"Approval Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"request_step_id","approver_id"},
     *           @OA\Property(property="request_step_id", type="integer", example=112),
     *           @OA\Property(property="approver_id", type="integer", example=44),
     *           @OA\Property(property="comment", type="string", example="Need extra documents")
     *        )
     *     ),
     *     @OA\Response(response=200, description="Step returned successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function returnBack(Request $request)
    {
        $request->validate([
            'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
            'approver_id'     => 'required|integer',
            'comment'         => 'nullable|string'
        ]);

        $result = $this->service->returnBack($request->all());
        return response()->json(['success' => true, 'data' => $result]);
    }
public function getMyPermissions(Request $request)
{
    $request->validate([
        'process_type' => 'required|string',
        'process_id'   => 'required|integer'
    ]);

    $userId = auth()->id();
    $roleId = auth()->user()->role ?? null;

    $result = $this->service->getMyPermissions(
        $request->process_type, 
        $request->process_id,
        $userId,
        $roleId
    );

    return response()->json([
        'success' => true,
        'data' => $result
    ]);
}


}
