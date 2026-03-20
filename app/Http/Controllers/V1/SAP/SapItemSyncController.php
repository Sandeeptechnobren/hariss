<?php

namespace App\Http\Controllers\V1\SAP;

use App\Http\Controllers\Controller;
use App\Services\V1\SAP\SapItemSyncService;


class SapItemSyncController extends Controller
{
    protected $service;

    public function __construct(SapItemSyncService $service)
    {
        $this->service = $service;
    }

    public function sync()
    {
        return response()->json(
            $this->service->sync()
        );
    }

    public function test()
    {
        $json = file_get_contents(storage_path('app/test_sap.json'));
        $payload = json_decode($json);
        // dd($payload);

        $service = new \App\Services\V1\SAP\SapItemSyncService();

        return response()->json(
            $service->syncFromStdClass($payload)
        );
    }
}
