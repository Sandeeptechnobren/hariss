<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\EfrisAPI\UraDeliverySyncService;

class UraDeliverySyncController extends Controller
{
    protected $service;

    public function __construct(UraDeliverySyncService $service)
    {
        $this->service = $service;
    }

    public function syncDelivery(Request $request)
    {
        $request->validate([
            'delivery_id' => 'required'
        ]);

        $result = $this->service->syncDelivery($request->delivery_id);

        return response()->json($result);
    }


    public function getUnsyncDelivery(Request $request)
    {
        $filters = $request->input('filter', []);

        if (!empty($filters['warehouse_id']) && !is_array($filters['warehouse_id'])) {
            $filters['warehouse_id'] = [$filters['warehouse_id']];
        }

        $request->validate([
            'filter.from_date'    => 'required|date',
            'filter.to_date'      => 'required|date',
            'filter.warehouse_id' => 'required'
        ]);

        $data = $this->service->getUnsyncDelivery($filters);

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
