<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Salesman;
use App\Models\VisitPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SalesTeamTrackingService
{
    // public function getRouteBySalesmanId(int $salesmanId): array
    // {
    //     // 🔹 Fetch salesman details
    //     $salesman = Salesman::select('id', 'name', 'osa_code', 'contact_no', 'warehouse_id')
    //         ->where('id', $salesmanId)
    //         ->firstOrFail();

    //     // 🔹 Fetch route tracking data
    //     $routes = VisitPlan::where('salesman_id', $salesmanId)
    //         ->orderBy('id')
    //         ->get(['latitude', 'longitude', 'visit_start_time']);

    //     return [
    //         'distributor_id' => (string) $salesman->warehouse_id,
    //         'sales_team' => [
    //             'id'    => (string) $salesman->id,
    //             'name'  => $salesman->name,
    //             'code'  => $salesman->osa_code,
    //             'phone' => $salesman->contact_no,
    //         ],
    //         'route' => $routes->map(function ($route) {
    //             return [
    //                 'lat'  => (float) $route->latitude,
    //                 'lng'  => (float) $route->longitude,
    //                 'time' => $route->time,
    //             ];
    //         })->toArray()
    //     ];
    // }

    public function getStaticRouteResponse(): array
    {
        return [
            'distributor_id' => '12',
            'sales_team' => [
                'id'    => '45',
                'name'  => 'Rahul Verma',
                'code'  => 'ST-045',
                'phone' => '9876543210',
            ],
            'route' => [
                [
                    'lat'  => 28.6139,
                    'lng'  => 77.209,
                    'time' => '09:00 AM',
                    'type' => 'start',
                ],
                [
                    'lat'  => 28.6142,
                    'lng'  => 77.2135,
                    'time' => '10:05 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.61,
                    'lng'  => 77.2125,
                    'time' => '10:20 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.6165,
                    'lng'  => 77.2235,
                    'time' => '10:35 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.619,
                    'lng'  => 77.214,
                    'time' => '10:50 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.62,
                    'lng'  => 77.2155,
                    'time' => '11:10 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.6195,
                    'lng'  => 77.2265,
                    'time' => '11:30 AM',
                    'type' => 'checkin',
                ],
                [
                    'lat'  => 28.6205,
                    'lng'  => 77.2275,
                    'time' => '12:45 PM',
                    'type' => 'end',
                ],
            ]
        ];
    }
}
