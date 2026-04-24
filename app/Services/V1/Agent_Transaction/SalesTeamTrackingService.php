<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Salesman;
use App\Models\VisitPlan;
use App\Models\SalesmanLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesTeamTrackingService
{
    // public function getStaticRouteResponse($request): array
    // {
    //     $visits = VisitPlan::select(
    //         'id',
    //         'osa_code',
    //         'salesman_id',
    //         'customer_id',
    //         'warehouse_id',
    //         'route_id',
    //         'latitude',
    //         'longitude',
    //         'visit_start_time',
    //         'visit_end_time',
    //         'shop_status',
    //         'remark'
    //     )
    //         ->with(['customer', 'salesman', 'warehouse'])
    //         ->where('salesman_id', $request->salesman_id)
    //         ->where('warehouse_id', $request->warehouse_id)
    //         ->whereBetween('visit_start_time', [
    //             Carbon::parse($request->from_date)->startOfDay(),
    //             Carbon::parse($request->to_date)->endOfDay(),
    //         ])
    //         ->orderBy('visit_start_time', 'asc')
    //         ->get();
    //     // dd($visits);
    //     if ($visits->isEmpty()) {
    //         return [];
    //     }

    //     $startVisit = $visits->first();

    //     $start = [
    //         'lat'  => (float) $startVisit->latitude,
    //         'lng'  => (float) $startVisit->longitude,
    //         'time' => optional($startVisit->visit_start_time)->format('h:i:s A '),
    //     ];
    //     // dd($visits);
    //     $firstVisit = $visits->first();

    //     $warehouse = [
    //         'id' => $firstVisit->warehouse_id,
    //         'warehouse_name' => optional($firstVisit->warehouse)->warehouse_name,
    //         'warehouse_code' => optional($firstVisit->warehouse)->warehouse_code,
    //     ];

    //     $customers = $visits->map(function ($visit) {
    //         return [
    //             'visit_plan_code' => $visit->osa_code,

    //             'customer' => [
    //                 'id' => $visit->customer_id,
    //                 'customer_name' => optional($visit->customer)->name,
    //                 'customer_code' => optional($visit->customer)->osa_code,
    //                 'phone' => optional($visit->customer)->contact_no,


    //                 'lat' => (float) optional($visit->customer)->latitude,
    //                 'lng' => (float) optional($visit->customer)->longitude,

    //                 'start_time' => optional($visit->visit_start_time)->format('h:i:s A'),
    //                 'end_time'   => optional($visit->visit_end_time)->format('h:i:s A'),

    //                 'shop_status' => $visit->shop_status,
    //                 'remark'      => $visit->remark,
    //             ],
    //         ];
    //     })->values();

    //     return [
    //         'start' => $start,
    //         'warehouse' => $warehouse,
    //         'customers' => $customers
    //     ];
    // }

    public function getSalesmen($warehouseId)
    {
        return Salesman::select('id', 'uuid', 'osa_code', 'name')
            ->where('status', 1)
            ->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhereRaw("? = ANY(string_to_array(warehouse_id, ','))", [$warehouseId]);
            })
            ->get();
    }

    public function getSalesmanLocationsWithCustomers($salesmanId, $warehouseId, $date)
    {
        // ✅ Only single date
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate   = Carbon::parse($date)->endOfDay();

        $locations = SalesmanLocation::where('salesman_id', $salesmanId)
            ->where('warehouse_id', $warehouseId)
            ->with('warehouse', 'salesman')
            ->get();

        $salesman  = optional($locations->first()->salesman);
        $warehouse = optional($locations->first()->warehouse);

        $visits = VisitPlan::with('customer:id,name,osa_code,uuid')
            ->where('salesman_id', $salesmanId)
            ->where('warehouse_id', $warehouseId)
            // ✅ filter only one date
            ->whereDate('visit_start_time', $date)
            ->get();

        $visitMap = [];

        foreach ($visits as $visit) {
            $key = $this->makeKey(
                $visit->latitude,
                $visit->longitude,
                Carbon::parse($visit->visit_start_time)->toDateString()
            );

            $visitMap[$key] = [
                'customer_id' => $visit->customer_id,
                'visit_id'    => $visit->id,
                'customer'    => $visit->customer ? [
                    'customer_id'   => $visit->customer->id,
                    'customer_name' => $visit->customer->name,
                    'customer_code' => $visit->customer->osa_code,
                    'customer_uuid' => $visit->customer->uuid,
                ] : null
            ];
        }

        $allLocations = [];

        foreach ($locations as $row) {

            if (empty($row->location)) continue;

            foreach ($row->location as $loc) {

                $locTime = Carbon::parse($loc['time']);

                // ✅ Single date filter
                if (!$locTime->isSameDay($startDate)) {
                    continue;
                }

                $key = $this->makeKey(
                    $loc['lat'],
                    $loc['lng'],
                    $locTime->toDateString()
                );

                $match = $visitMap[$key] ?? null;

                $allLocations[] = [
                    'lat'      => $loc['lat'],
                    'lng'      => $loc['lng'],
                    'time'     => $locTime->toDateTimeString(),
                    'customer' => $match['customer'] ?? null,
                    'visit_id' => $match['visit_id'] ?? null,
                ];
            }
        }

        return [
            'salesman' => [
                'id'             => $salesman->id ?? null,
                'salesman_name'  => $salesman->name ?? null,
                'salesman_code'  => $salesman->osa_code ?? null,
                'salesman_uuid'  => $salesman->uuid ?? null,
            ],
            'warehouse' => [
                'id'             => $warehouse->id ?? null,
                'warehouse_name' => $warehouse->warehouse_name ?? null,
                'warehouse_code' => $warehouse->warehouse_code ?? null,
                'warehouse_uuid' => $warehouse->uuid ?? null,
            ],
            'total_locations' => count($allLocations),

            // ⚠️ FIX: customer_id missing tha, ab customer se check karenge
            'matched_locations' => collect($allLocations)
                ->whereNotNull('customer')
                ->count(),

            'locations' => collect($allLocations)
                ->unique(fn($item) => $item['lat'] . '_' . $item['lng'] . '_' . $item['time'])
                ->sortBy('time')
                ->values()
        ];
    }
    private function makeKey($lat, $lng, $date)
    {
        return round($lat, 6) . '_' . round($lng, 6) . '_' . $date;
    }
}
