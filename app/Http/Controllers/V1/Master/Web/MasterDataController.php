<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Import models
use App\Models\Warehouse;
use App\Models\Vehicle;
use App\Models\Route;
use App\Models\Item;
use App\Models\Salesman;
use App\Models\RouteVisit;
use App\Models\AgentCustomer;
use App\Models\CompanyCustomer;

class MasterDataController extends Controller
{
    /**
     * Master Data API
     * model = warehouse | vehicle | route | item | depot
     * status = true | false (optional)
     */
    public function index(Request $request)
    {
        $modelKey = $request->get('model');
        $perPage  = $request->get('per_page', 10);
        $status   = $request->get('status'); // true / false (string)

        $masterModels = [
            'warehouse'       => Warehouse::class,
            'vehicle'         => Vehicle::class,
            'route'           => Route::class,
            'item'            => Item::class,
            'salesman'        => Salesman::class,
            'routeVisit'      => RouteVisit::class,
            'agentCustomer'   => AgentCustomer::class,
            'companyCustomer' => CompanyCustomer::class,
        ];

        if (!isset($masterModels[$modelKey])) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid master model'
            ], 400);
        }

        $modelClass = $masterModels[$modelKey];

        /**
         * Map URL true/false → DB 1/0 (SORTING ONLY)
         */
        $inactiveFirst = ($status === 'false');

        if ($inactiveFirst) {
            // false → inactive (0) first
            $orderSql = "
            CASE
                WHEN status = 0 THEN 1
                WHEN status = 1 THEN 2
                ELSE 3
            END
        ";
        } else {
            // true or null → active (1) first
            $orderSql = "
            CASE
                WHEN status = 1 THEN 1
                WHEN status = 0 THEN 2
                ELSE 3
            END
        ";
        }

        $data = $modelClass::query()
            ->orderByRaw($orderSql)
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'model'  => $modelKey,
            'sort'   => $inactiveFirst ? 'inactive_first' : 'active_first',
            'data'   => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
            ]
        ]);
    }
}
