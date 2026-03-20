<?php

// namespace App\Http\Controllers\V1\Approval_process;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Services\V1\Approval_process\HtappWorkflowStatusService;

// class HtappWorkflowStatusController extends Controller
// {
//     protected $service;

//     public function __construct(HtappWorkflowStatusService $service)
//     {
//         $this->service = $service;
//     }

//     public function getStatus(Request $request)
//     {
//         $request->validate([
//             'process_type' => 'required|string',
//             'process_id'   => 'required|integer'
//         ]);
//         $result = $this->service->getStatus($request->all());
//         return response()->json([
//             'success' => true,
//             'data'    => $result
//         ]);
//     }
// }
namespace App\Http\Controllers\V1\Approval_process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\Approval_process\HtappWorkflowStatusService;

/**
 * @OA\Tag(
 *     name="Approval Workflow Status",
 *     description="Track status of workflow approval on any process"
 * )
 */
class HtappWorkflowStatusController extends Controller
{
    protected $service;

    public function __construct(HtappWorkflowStatusService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/approval/workflow/status",
     *     summary="Get workflow approval status for a specific process",
     *     tags={"Approval Workflow Status"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="process_type",
     *         in="query",
     *         required=true,
     *         description="Process key defined in htapp_workflow_models.model_key",
     *         @OA\Schema(type="string", example="order")
     *     ),
     *
     *     @OA\Parameter(
     *         name="process_id",
     *         in="query",
     *         required=true,
     *         description="Actual record ID of the selected process",
     *         @OA\Schema(type="integer", example=3102)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Workflow status successfully fetched",
     *         @OA\JsonContent(
     *            @OA\Property(property="success", type="boolean", example=true),
     *            @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     )
     * )
     */
    public function getStatus(Request $request)
    {
        $request->validate([
            'process_type' => 'required|string',
            'process_id'   => 'required|integer'
        ]);

        $result = $this->service->getStatus($request->all());

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }

    public function getOrderApprovalStatus(Request $request)
    {
        $request->validate([
            'order_code' => 'required|string'
        ]);

        $result = $this->service->getOrderDeliveriesInvoicesApprovalByOrderCode(
            $request->get('order_code')
        );

        return response()->json([
            'success' => true,
            'data'    => $result
        ]);
    }
}
