<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\UraSyncService;
use Illuminate\Http\Request;

class UraSyncController extends Controller
{
    protected $service;

    public function __construct(UraSyncService $service)
    {
        $this->service = $service;
    }

    public function sync(Request $request)
    {
        $itemId = $request->input('item_id', 'all');

        $result = $this->service->syncItems($itemId);

        return response()->json($result);
    }
}
