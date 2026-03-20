<?php

// namespace App\Http\Controllers\V1\Approval_process;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Services\V1\Approval_process\HtappWorkflowApprovalService;

// class HtappWorkflowApprovalController extends Controller
// {
//     protected $service;

//     public function __construct(HtappWorkflowApprovalService $service)
//     {
//         $this->service = $service;
//     }

//     public function startApproval(Request $request)
//     {
//         // $request->validate([
//         //     'workflow_id'   => 'required|integer|exists:htapp_workflows,id',
//         //     'process_type'  => 'required|string',
//         //     'process_id'    => 'required|integer'
//         // ]);
//         $request->validate([
//             'workflow_id'  => 'required|integer|exists:htapp_workflows,id',
//             'process_type'  => 'required|string|exists:htapp_workflow_models,model_key',
//             'process_id'   => 'required|integer',
//         ]);
//         $result = $this->service->startApproval($request->all());

//         return response()->json([
//             'success' => true,
//             'data'    => $result
//         ]);
//     }
// }
namespace App\Http\Controllers\V1\Approval_process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\Approval_process\HtappWorkflowApprovalService;

/**
 * @OA\Tag(
 *     name="Approval Workflow",
 *     description="Workflow execution and approval actions"
 * )
 */
class HtappWorkflowApprovalController extends Controller
{
    protected $service;

    public function __construct(HtappWorkflowApprovalService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/start",
     *     tags={"Approval Workflow"},
     *     summary="Start approval request for a process",
     *     description=" Assigns workflow to a process record and creates approval steps & approvers snapshot",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"workflow_id","process_type","process_id"},
     *
     *           @OA\Property(
     *             property="workflow_id",
     *             type="integer",
     *             example=8,
     *             description="Workflow ID to start"
     *           ),
     *           @OA\Property(
     *             property="process_type",
     *             type="string",
     *             example="order",
     *             description="Must exist in htapp_workflow_models table"
     *           ),
     *           @OA\Property(
     *             property="process_id",
     *             type="integer",
     *             example=3102,
     *             description="Primary key of selected process record"
     *           )
     *        )
     *     ),
     *
     *     @OA\Response(
     *        response=200,
     *        description="Approval workflow started successfully",
     *        @OA\JsonContent(
     *            @OA\Property(property="success", type="boolean", example=true),
     *            @OA\Property(property="data", type="object",
     *                @OA\Property(property="success", type="boolean", example=true),
     *                @OA\Property(property="workflow_request_uuid", type="string", example="b35d1b21-8e5a-4aff-9a29-0fc56d36ac2a"),
     *                @OA\Property(property="workflow_status", type="string", example="PENDING")
     *            )
     *        )
     *     ),
     *
     *     @OA\Response(
     *        response=422,
     *        description="Validation error"
     *     )
     * )
     */
    public function startApproval(Request $request)
    {
        $request->validate([
            'workflow_id'  => 'required|integer|exists:htapp_workflows,id',
            'process_type' => 'required|string|exists:htapp_workflow_models,model_key',
            'process_id'   => 'required|integer',
        ]);

        $result = $this->service->startApproval($request->all());

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }
}
