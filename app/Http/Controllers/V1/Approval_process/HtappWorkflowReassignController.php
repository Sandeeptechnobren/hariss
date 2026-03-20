<?php

// namespace App\Http\Controllers\V1\Approval_process;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Services\V1\Approval_process\HtappWorkflowReassignService;

// class HtappWorkflowReassignController extends Controller
// {
//     protected $service;

//     public function __construct(HtappWorkflowReassignService $service)
//     {
//         $this->service = $service;
//     }

//     public function reassign(Request $request)
//     {
//         $request->validate([
//             'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
//             'new_user_id'     => 'required|integer|exists:users,id'
//         ]);

//         $result = $this->service->reassign($request->only(['request_step_id','new_user_id','note']));

//         return response()->json(['success' => true, 'data' => $result]);
//     }
// }
namespace App\Http\Controllers\V1\Approval_process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\Approval_process\HtappWorkflowReassignService;

/**
 * @OA\Tag(
 *     name="Approval Actions",
 *     description="Approve, Reject, Return Back & Reassign steps"
 * )
 */
class HtappWorkflowReassignController extends Controller
{
    protected $service;

    public function __construct(HtappWorkflowReassignService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/reassign",
     *     summary="Reassign workflow step to another user",
     *     tags={"Approval Actions"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"request_step_id","new_user_id"},
     *           @OA\Property(property="request_step_id", type="integer", example=150),
     *           @OA\Property(property="new_user_id", type="integer", example=46),
     *           @OA\Property(property="note", type="string", example="Reassigned due to leave")
     *        )
     *     ),
     *
     *     @OA\Response(
     *        response=200,
     *        description="Reassignment successful",
     *        @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example=true),
     *          @OA\Property(property="data", type="object",
     *               @OA\Property(property="old_user", type="integer", example=44),
     *               @OA\Property(property="new_user", type="integer", example=46),
     *               @OA\Property(property="status", type="string", example="UPDATED")
     *          )
     *        )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function reassign(Request $request)
    {
        $request->validate([
            'request_step_id' => 'required|integer|exists:htapp_workflow_request_steps,id',
            'new_user_id'     => 'required|integer|exists:users,id'
        ]);

        $result = $this->service->reassign($request->only(['request_step_id','new_user_id','note']));

        return response()->json(['success' => true, 'data' => $result]);
    }
}
