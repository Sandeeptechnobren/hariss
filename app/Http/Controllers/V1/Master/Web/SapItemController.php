<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\MasterServices\Web\SapItemService;

class SapItemController extends Controller
{
     protected $service;

    public function __construct(SapItemService $service)
    {
        $this->service = $service;
    }

    public function importFromSAP()
    {
        $result = $this->service->importFromSAP();
        if ($result['status']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'inserted' => $result['inserted'] ?? null,
                'updated' => $result['updated'] ?? null,
                'item_uoms_inserted' => $result['item_uoms_inserted'] ?? null,
                'item_uoms_updated' => $result['item_uoms_updated'] ?? null,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }
    }
}