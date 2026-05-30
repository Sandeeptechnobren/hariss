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

    // public function getSalesmanLocationsWithCustomers($salesmanId, $warehouseId, $date)
    // {
    //     $startDate = Carbon::parse($date)->startOfDay();

    //     $locations = SalesmanLocation::where('salesman_id', $salesmanId)
    //         ->where('warehouse_id', $warehouseId)
    //         ->with(['warehouse', 'salesman'])
    //         ->get();

    //     if ($locations->isEmpty()) {
    //         return null;
    //     }

    //     $salesman  = $locations->first()->salesman;
    //     $warehouse = $locations->first()->warehouse;

    //     $visits = VisitPlan::with('customer:id,name,osa_code,uuid')
    //         ->where('salesman_id', $salesmanId)
    //         ->where('warehouse_id', $warehouseId)
    //         ->whereDate('visit_start_time', $date)
    //         ->get();

    //     $allLocations = [];
    //     $usedVisits = [];

    //     foreach ($locations as $location) {

    //         if (empty($location->location)) {
    //             continue;
    //         }

    //         foreach ($location->location as $loc) {

    //             $locTime = Carbon::parse($loc['time']);

    //             if (!$locTime->isSameDay($startDate)) {
    //                 continue;
    //             }

    //             $match = collect($visits)
    //                 ->filter(fn($visit) => !in_array($visit->id, $usedVisits))
    //                 ->map(function ($visit) use ($loc, $locTime) {

    //                     $distance = $this->calculateDistance(
    //                         $loc['lat'],
    //                         $loc['lng'],
    //                         $visit->latitude,
    //                         $visit->longitude
    //                     );

    //                     $timeDiff = abs(
    //                         Carbon::parse($visit->visit_start_time)
    //                             ->diffInSeconds($locTime)
    //                     );

    //                     return [
    //                         'visit'    => $visit,
    //                         'distance' => $distance,
    //                         'timeDiff' => $timeDiff,
    //                         'score'    => $timeDiff + ($distance * 2)
    //                     ];
    //                 })
    //                 ->filter(fn($item) => $item['distance'] <= 15 && $item['timeDiff'] >= 120)
    //                 ->sortBy('score')
    //                 ->first();

    //             $visit = $match['visit'] ?? null;

    //             if ($visit) {
    //                 $usedVisits[] = $visit->id;
    //             }

    //             $allLocations[] = [
    //                 'lat'  => $loc['lat'],
    //                 'lng'  => $loc['lng'],
    //                 'time' => $locTime->toDateTimeString(),

    //                 'customer' => $visit && $visit->customer ? [
    //                     'customer_id'        => $visit->customer->id,
    //                     'customer_name'      => $visit->customer->name,
    //                     'customer_code'  => $visit->customer->osa_code,
    //                     'customer_uuid'      => $visit->customer->uuid,
    //                 ] : null,

    //                 'visit_id' => $visit->id ?? null,
    //             ];
    //         }
    //     }

    //     return [
    //         'salesman' => [
    //             'id'             => $salesman->id ?? null,
    //             'salesman_name'  => $salesman->name ?? null,
    //             'salesman_code'  => $salesman->osa_code ?? null,
    //             'salesman_uuid'  => $salesman->uuid ?? null,
    //         ],
    //         'warehouse' => [
    //             'id'             => $warehouse->id ?? null,
    //             'warehouse_name' => $warehouse->warehouse_name ?? null,
    //             'warehouse_code' => $warehouse->warehouse_code ?? null,
    //             'warehouse_uuid' => $warehouse->uuid ?? null,
    //         ],
    //         'total_locations' => count($allLocations),

    //         'matched_locations' => collect($allLocations)
    //             ->whereNotNull('customer')
    //             ->count(),

    //         'locations' => collect($allLocations)
    //             ->unique(fn($item) => $item['lat'] . '_' . $item['lng'] . '_' . $item['time'])
    //             ->sortBy('time')
    //             ->values()
    //     ];
    // }

    public function getSalesmanLocationsWithCustomers($salesmanId, $warehouseId, $date)
    {
        $startDate = Carbon::parse($date)->startOfDay();

        $locations = SalesmanLocation::where('salesman_id', $salesmanId)
            ->where('warehouse_id', $warehouseId)
            ->with(['warehouse', 'salesman'])
            ->get();

        if ($locations->isEmpty()) {
            return null;
        }

        $salesman  = $locations->first()->salesman;
        $warehouse = $locations->first()->warehouse;

        $visits = VisitPlan::with('customer:id,name,osa_code,uuid')
            ->where('salesman_id', $salesmanId)
            ->where('warehouse_id', $warehouseId)
            ->whereDate('visit_start_time', $date)
            ->orderBy('visit_start_time')
            ->get();

        $rawPoints = [];

        foreach ($locations as $location) {
            if (empty($location->location)) {
                continue;
            }

            foreach ($location->location as $loc) {
                $locTime = Carbon::parse($loc['time']);

                if (!$locTime->isSameDay($startDate)) {
                    continue;
                }

                $rawPoints[] = [
                    'lat'  => (float) $loc['lat'],
                    'lng'  => (float) $loc['lng'],
                    'time' => $locTime,
                ];
            }
        }

        if (empty($rawPoints)) {
            return null;
        }

        usort($rawPoints, fn($a, $b) => $a['time']->timestamp <=> $b['time']->timestamp);

        $dedupedPoints = [];
        foreach ($rawPoints as $point) {
            $last = end($dedupedPoints);
            if (
                $last &&
                round($last['lat'], 6) === round($point['lat'], 6) &&
                round($last['lng'], 6) === round($point['lng'], 6) &&
                $last['time']->equalTo($point['time'])
            ) {
                continue;
            }
            $dedupedPoints[] = $point;
        }

        $usedPointIndexes = [];
        $visitMatches     = [];

        foreach ($visits->sortBy('visit_start_time') as $visit) {
            $visitTime = Carbon::parse($visit->visit_start_time);
            $bestScore = PHP_INT_MAX;
            $bestIndex = null;

            foreach ($dedupedPoints as $index => $point) {
                if (in_array($index, $usedPointIndexes)) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $point['lat'],
                    $point['lng'],
                    (float) $visit->latitude,
                    (float) $visit->longitude
                );

                $timeDiff = abs($visitTime->diffInSeconds($point['time']));
                // dd($timeDiff);
                $score = $timeDiff + ($distance * 3);

                if ($score < $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex !== null) {
                $usedPointIndexes[]        = $bestIndex;
                $visitMatches[$visit->id]  = $bestIndex;
            }
        }

        $pointToVisit = [];
        foreach ($visitMatches as $visitId => $pointIndex) {
            $pointToVisit[$pointIndex] = $visits->firstWhere('id', $visitId);
        }

        $allLocations = [];

        foreach ($dedupedPoints as $index => $point) {
            $visit = $pointToVisit[$index] ?? null;

            $duration = null;
            if (isset($dedupedPoints[$index + 1])) {
                $nextTime    = $dedupedPoints[$index + 1]['time'];
                $diffSeconds = $point['time']->diffInSeconds($nextTime);

                $duration = [
                    'seconds' => $diffSeconds,
                    'minutes' => round($diffSeconds / 60, 2),
                    'human'   => $this->formatDuration($diffSeconds),
                ];
            }

            $allLocations[] = [
                'lat'      => $point['lat'],
                'lng'      => $point['lng'],
                'time'     => $point['time']->toDateTimeString(),
                'duration' => $duration,

                'customer' => $visit && $visit->customer ? [
                    'customer_id'   => $visit->customer->id,
                    'customer_name' => $visit->customer->name,
                    'customer_code' => $visit->customer->osa_code,
                    'customer_uuid' => $visit->customer->uuid,
                ] : null,

                'visit_id' => $visit->id ?? null,
            ];
        }

        return [
            'salesman' => [
                'id'            => $salesman->id ?? null,
                'salesman_name' => $salesman->name ?? null,
                'salesman_code' => $salesman->osa_code ?? null,
                'salesman_uuid' => $salesman->uuid ?? null,
            ],
            'warehouse' => [
                'id'             => $warehouse->id ?? null,
                'warehouse_name' => $warehouse->warehouse_name ?? null,
                'warehouse_code' => $warehouse->warehouse_code ?? null,
                'warehouse_uuid' => $warehouse->uuid ?? null,
            ],

            'total_locations'   => count($allLocations),
            'matched_locations' => collect($allLocations)->whereNotNull('customer')->count(),
            'locations'         => $allLocations,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} sec";
        }

        $minutes = intdiv($seconds, 60);
        $secs    = $seconds % 60;

        if ($minutes < 60) {
            return $secs > 0 ? "{$minutes} min {$secs} sec" : "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return $mins > 0 ? "{$hours} hr {$mins} min" : "{$hours} hr";
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
