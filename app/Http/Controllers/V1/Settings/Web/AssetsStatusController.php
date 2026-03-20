<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\Settings\Web\AssetsStatusService;
use Illuminate\Http\JsonResponse;

class AssetsStatusController extends Controller
{
    protected $service;

    public function __construct(AssetsStatusService $service)
    {
        $this->service = $service;
    }

    public function dropdown(): JsonResponse
    {
        $statuses = $this->service->getDropdownStatuses();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Assets status list fetched successfully',
            'data'    => $statuses
        ], 200);
    }
}
