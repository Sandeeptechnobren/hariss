<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\Route;
use App\Models\Warehouse;

class UraCustomerValidateService extends BaseEfrisService
{
    public function customerValidate($tin, $routeId)
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json(['status' => false, 'message' => 'Route not found']);
        }

        $warehouse = Warehouse::where('id', $route->warehouse_id)
            ->where('is_efris', 1)
            ->where('status', 1)
            ->first();

        if (!$warehouse) {
            return response()->json(['status' => false, 'message' => 'EFRIS warehouse not found']);
        }

        $payload = (object)[
            "tin" => $tin,
            "ninBrn" => ""
        ];

        $response = $this->makePost("T119", $payload, $warehouse);
        // dd($response);
        return $this->formatResponse($response);
    }

    private function formatResponse($response)
    {
        $inner = $response['inner_response'] ?? [];

        if (isset($inner['taxpayer'])) {
            return response()->json([
                'status' => true,
                'name' => $inner['taxpayer']['legalName'] ?? '',
                'address' => $inner['taxpayer']['address'] ?? '',
                'email' => $inner['taxpayer']['contactEmail'] ?? '',
                'contact' => $inner['taxpayer']['contactNumber'] ?? '',
                'cust_type' => $inner['taxpayer']['taxpayerType'] ?? ''
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => $response['message']
        ]);
    }


    public function getBranch($warehouseId)
    {

        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return [
                'success' => false,
                'message' => 'Warehouse not found'
            ];
        }
        $payload119 = [
            "tin" => (string)$warehouse->tin_no
        ];

        $resp119 = $this->callApi("T119", $payload119, $warehouse);
        $taxpayer = $resp119['inner_response']['taxpayer'] ?? [];

        $resp138 = $this->callApi("T138", [], $warehouse);
        $branches = $resp138['inner_response'] ?? [];

        // 🔥 FINAL FORMAT (IMPORTANT)
        $final = [];

        foreach ($branches as $value) {

            $final[] = [
                "tin" => $taxpayer['tin'] ?? null,
                "branchId" => $value['branchId'] ?? null,
                "branchName" => $value['branchName'] ?? null,
                "ninBrn" => $taxpayer['ninBrn'] ?? null,
                "address" => $taxpayer['address'] ?? null
            ];
        }

        return [
            'success' => true,
            'data' => $final
        ];
    }
}
