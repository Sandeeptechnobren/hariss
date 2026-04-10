<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\UraReturnService;
use Illuminate\Http\Request;

class UraReturnController extends Controller
{
    protected $service;

    public function __construct(UraReturnService $service)
    {
        $this->service = $service;
    }

    public function getReturnsList(Request $request)
    {
        $request->validate([
            'filter.from_date' => 'required|date',
            'filter.to_date' => 'required|date',
            'filter.warehouse_id' => 'required'
        ]);

        $filter = $request->input('filter');

        $response = $this->service->getReturnsList(
            $filter['warehouse_id'],
            $filter['from_date'],
            $filter['to_date']
        );

        return response()->json($response);
    }


    public function getReturnDetails(Request $request)
    {
        $request->validate([
            'filter.invoiceNo' => 'required',
            'filter.warehouse_id' => 'required'
        ]);

        $filter = $request->input('filter');

        $response = $this->service->getReturnDetails(
            $filter['invoiceNo'],
            $filter['warehouse_id']
        );

        return response()->json($response);
    }


    public function syncReturn(Request $request)
    {
        $response = $this->service->syncReturn($request->all());
        return response()->json($response);
    }
}
