<?php

namespace App\Http\Controllers\V1\Approval_process;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\V1\Approval_process\ApprovalHistoryService;
use Exception;

class ApprovalHistoryController extends Controller
{
    public function __construct(
        protected ApprovalHistoryService $service
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'process_type' => 'required|string',
            'process_id'   => 'required|integer'
        ]);

        try {

            $data = $this->service->getHistory(
                $request->process_type,
                $request->process_id
            );

            return response()->json([
                'status'  => true,
                'message' => 'Approval history fetched successfully',
                'data'    => $data
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}