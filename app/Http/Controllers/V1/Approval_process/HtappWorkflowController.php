<?php


namespace App\Http\Controllers\V1\Approval_process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\V1\Approval_process\HtappWorkflowService;

/**
 * @OA\Tag(
 *     name="Approval Workflow",
 *     description="Manage Workflows and Approval Processes"
 * )
 *
 */
class HtappWorkflowController extends Controller
{
    protected $service;
    public function __construct(HtappWorkflowService $service)
    {
        $this->service = $service;
    }
    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/save",
     *     summary="Create new workflow with steps",
     *     tags={"Approval Workflow"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *           required={"name","is_active","steps"},
     *           @OA\Property(property="name", type="string", example="Order Approval"),
     *           @OA\Property(property="description", type="string", example="This is workflow for approval"),
     *           @OA\Property(property="is_active", type="boolean"),
     *           @OA\Property(
     *             property="steps",
     *             type="array",
     *             @OA\Items(
     *                @OA\Property(property="step_order", type="integer", example=1),
     *                @OA\Property(property="title", type="string", example="Manager Approval"),
     *                @OA\Property(property="approval_type", type="string", enum={"AND","OR"}),
     *                @OA\Property(property="message", type="string"),
     *                @OA\Property(property="notification", type="string"),
     *                @OA\Property(property="user_ids", type="array", @OA\Items(type="integer")),
     *                @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
     *                @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *           )
     *        )
     *     ),
     *    @OA\Response(response=200, description="Workflow created successfully")
     * )
     */
    public function saveWorkflow(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'required|boolean',
            'steps'                 => 'required|array|min:1',
            'steps.*.step_order'    => 'required|integer|min:1',
            'steps.*.title'         => 'required|string|max:255',
            'steps.*.approval_type' => 'required|in:AND,OR',
            'steps.*.message'       => 'nullable|string',
            'steps.*.confirmationMessage'       => 'nullable|string',
            'steps.*.notification'  => 'nullable|string',
            'steps.*.user_ids'      => 'nullable|array',
            'steps.*.user_ids.*'    => 'nullable|integer|exists:users,id',
            'steps.*.role_ids'      => 'nullable|array',
            'steps.*.role_ids.*'    => 'nullable|integer|exists:roles,id',
            'steps.*.permissions'   => 'nullable|array',
            'steps.*.permissions.*' => 'in:ADD,APPROVE,REJECT,UPDATE,RETURN_BACK,EDIT_BEFORE_APPROVAL',
        ]);
        $workflow = $this->service->saveWorkflow($request->all());
        return response()->json(['success' => true, 'data' => $workflow]);
    }
    /**
     * @OA\Get(
     *     path="/api/master/approval/workflow/list",
     *     summary="Get all workflows",
     *     tags={"Approval Workflow"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Workflow list retrieved")
     * )
     */
    public function list()
    {
        $result = $this->service->getList();
        return response()->json(['success' => true, 'data' => $result]);
    }
    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/update",
     *     summary="Update workflow details and steps",
     *     tags={"Approval Workflow"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Workflow updated successfully")
     * )
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'workflow_id'   => 'required|integer|exists:htapp_workflows,id',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'is_active'     => 'required|boolean',
            'steps'                 => 'required|array',
            'steps.*.step_id'       => 'nullable|integer',//|exists:htapp_workflow_steps,id
            'steps.*.step_order'    => 'required|integer',
            'steps.*.title'         => 'required|string|max:255',
            'steps.*.approval_type' => 'required|in:AND,OR',
            'steps.*.message'       => 'nullable|string',
            'steps.*.notification'  => 'nullable|string',

            'steps.*.permissions'   => 'nullable|array',
            'steps.*.permissions.*' => 'in:ADD,APPROVE,REJECT,UPDATE,RETURN_BACK,EDIT_BEFORE_APPROVAL',
            'steps.*.confirmationMessage'   => 'nullable|string',
            'steps.*.confirmationMessage.*' => 'in:ADD,APPROVE,REJECT,UPDATE,RETURN_BACK,EDIT_BEFORE_APPROVAL',
            'steps.*.user_ids'      => 'nullable|array',
            'steps.*.user_ids.*'    => 'integer',

            'steps.*.role_ids'      => 'nullable|array',
            'steps.*.role_ids.*'    => 'integer'
        ]);

        $result = $this->service->updateWorkflow($validated);

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/approval/workflow/toggle",
     *     summary="Activate / deactivate workflow",
     *     tags={"Approval Workflow"},
     *     @OA\Response(response=200, description="Status updated")
     * )
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'workflow_id' => 'required|integer|exists:htapp_workflows,id',
            'is_active'   => 'required|boolean'
        ]);
        $result = $this->service->toggleWorkflow($request->all());
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * @OA\Get(
     *     path="/api/htapp/approval/workflow/{uuid}",
     *     summary="Get workflow details by UUID",
     *     tags={"Approval Workflow"},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Workflow details")
     * )
     */
    public function getWorkflowByUuid($uuid)
    {
        $result = $this->service->getWorkflowByUuid($uuid);
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/master/approval/workflow/process-types",
     *     summary="Get list of available approval process models",
     *     tags={"Approval Workflow"},
     *     @OA\Response(response=200, description="Process Types")
     * )
     */
    public function getProcessTypes()
    {
        $models = DB::table('htapp_workflow_models')->where('is_active', true)->select('model_key as process_type', 'display_name')->orderBy('display_name', 'ASC')->get();
        return response()->json([
            'success' => true,
            'data' => $models
        ]);
    }
    /**
     * @OA\POST(
     *     path="/api/master/approval/workflow/requests",
     *     summary="Get list of available approval process models",
     *     tags={"Approval Workflow"},
     *     @OA\Response(response=200, description="Process Types")
     * )
     */

    // public function getMyApprovalsByModel(Request $request)
    // {
    //     $model = $request->query('model');
    //     $user = auth()->user();
    //     if (!$model) {
    //         return response()->json(['success' => false, 'message' => 'Model is required'], 422);
    //     }
    //     $data = $this->service->getMyApprovalsByModel($user->id, $model);
    //     return response()->json(['success' => true, 'data' => $data]);
    // }
    public function getMyApprovalsByModel(Request $request)
    {
        $model = $request->query('model');
        $requestStepId = $request->query('request_step_id'); // ✅ added
        $user = auth()->user();

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Model is required'
            ], 422);
        }

        $data = $this->service->getMyApprovalsByModel(
            $user->id,
            $model,
            $requestStepId // ✅ added
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function assign(Request $request)
    {
        $request->validate([
            'process_type' => 'required|string|exists:htapp_workflow_models,model_key',
            'workflow_id'  => 'required|integer|exists:htapp_workflows,id',
            'is_active'    => 'sometimes|boolean'
        ]);
        $result = $this->service->assign($request->all());
        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }

    public function assignmentlist(Request $request)
    {
        $result = $this->service->assignmentlist();
        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }
    public function updateAssignedWorkflow(Request $request)
    {
        $request->validate([
            'process_type' => 'required|string|exists:htapp_workflow_models,model_key',
            'workflow_id'  => 'required|integer|exists:htapp_workflows,id',
            'is_active'    => 'nullable|boolean'
        ]);
        $result = $this->service->updateWorkflowAssignment($request->all());
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? null
        ]);
    }

    public function toggleAssignment(Request $request)
    {
        $request->validate([
            'process_type' => 'required|string|exists:htapp_workflow_models,model_key',
            'workflow_id'  => 'required|integer|exists:htapp_workflows,id',
            'is_active'    => 'required|boolean'
        ]);

        $result = $this->service->toggleAssignment($request->all());

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }
}
