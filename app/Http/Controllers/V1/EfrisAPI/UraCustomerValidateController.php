<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\EfrisAPI\UraCustomerValidateService;

class UraCustomerValidateController extends Controller
{
    protected $service;

    public function __construct(UraCustomerValidateService $service)
    {
        $this->service = $service;
    }

    public function customerValidate(Request $request)
    {
        if (!$request->tin_no || !$request->route_id) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ]);
        }

        return $this->service->customerValidate($request->tin_no, $request->route_id);
    }


    public function getBranch(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required'
        ]);

        $service = app(UraCustomerValidateService::class);

        $result = $service->getBranch($request->warehouse_id);

        return response()->json($result);
    }
}
