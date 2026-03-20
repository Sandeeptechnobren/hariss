<?php

namespace App\Services\V1\Loyality_Management;

use App\Models\Loyality_Management\Adjustment;
use App\Models\Loyality_Management\CustomerLoyalityPoint;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Loyality_Management\CustomerLoyalityActivity;
use App\Models\Tier;

class AdjustmentService
{
  public function createBonus(array $data): Adjustment
    {
        return DB::transaction(function () use ($data) {

            if ($data['adjustment_symbol'] == 1) {
                $data['closing_points'] =
                    $data['currentreward_points'] + $data['adjustment_points'];
            } else {
                $data['closing_points'] =
                    $data['currentreward_points'] - $data['adjustment_points'];
            }

            $adjustment = Adjustment::create($data);
            $this->applyAdjustmentToLoyalty($adjustment, $data);

            return $adjustment;
        });
    }

   private function applyAdjustmentToLoyalty(Adjustment $adjustment, array $data)
{
    $customerId = $data['customer_id'];
    $symbol     = $data['adjustment_symbol'];
    $points     = $data['adjustment_points'];

    $loyalty = CustomerLoyalityPoint::where('customer_id', $customerId)->first();

    $lastClosing = $loyalty ? $loyalty->total_closing : 0;
    $newClosing  = $symbol == 1
        ? $lastClosing + $points
        : $lastClosing - $points;

    $tier = Tier::where('minpurchase', '<=', $newClosing)
                ->where('maxpurchase', '>=', $newClosing)
                ->first();

    $tierId = $tier ? $tier->id : null;

    if (!$loyalty) {
        $loyalty = CustomerLoyalityPoint::create([
            'customer_id'   => $customerId,
            'total_earning' => $symbol == 1 ? $points : 0,
            'total_spend'   => $symbol == 2 ? $points : 0,
            'total_closing' => $newClosing,
            'tier_id'       => $tierId,    
        ]);
    } 
    else {
        if ($symbol == 1) {
            $loyalty->total_earning += $points;
            $loyalty->total_closing += $points;
        } else {
            $loyalty->total_spend += $points;
            $loyalty->total_closing -= $points;
        }
        $loyalty->tier_id = $tierId;      

        $loyalty->save();
    }
    CustomerLoyalityActivity::create([
        'header_id'        => $loyalty->id,
        'customer_id'      => $customerId,
        'activity_date'    => $adjustment->created_at,
        'activity_type'    => 'adjustment',
        'invoice_id'       => $adjustment->id,
        'description'      => $adjustment->description,
        'incooming_point'  => $symbol == 1 ? $points : 0,
        'outgoing_point'   => $symbol == 2 ? $points : 0,
        'adjustment_point' => $points,
        'closing_point'    => $newClosing,
    ]);
}

    public function getAllAdjustment(array $filters = [], int $perPage = 20)
{
    $query = Adjustment::with([
        'warehouse:id,warehouse_code,warehouse_name',
        'route:id,route_code,route_name',
        'customer:id,osa_code,name'
    ]);

    if (!empty($filters['osa_code'])) {
        $query->where('osa_code', 'ILIKE', "%{$filters['osa_code']}%");
    }

    if (!empty($filters['warehouse_id'])) {
        $query->where('warehouse_id', $filters['warehouse_id']);
    }

    if (!empty($filters['route_id'])) {
        $query->where('route_id', $filters['route_id']);
    }

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        $query->whereBetween('created_at', [
            $filters['from_date'], 
            $filters['to_date']
        ]);
    }

    if (!empty($filters['min_adjustment'])) {
        $query->where('adjustment_points', '>=', $filters['min_adjustment']);
    }

    if (!empty($filters['max_adjustment'])) {
        $query->where('adjustment_points', '<=', $filters['max_adjustment']);
    }


    if (!empty($filters['search'])) {

        $search = strtolower(trim($filters['search']));

        $query->where(function ($q) use ($search) {

            $q->where('osa_code', 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(currentreward_points AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(adjustment_points AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(description AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(closing_points AS TEXT)"), 'ILIKE', "%{$search}%");

            $q->orWhereHas('warehouse', function ($w) use ($search) {
                $w->where('warehouse_code', 'ILIKE', "%{$search}%")
                  ->orWhere('warehouse_name', 'ILIKE', "%{$search}%");
            });

            $q->orWhereHas('route', function ($r) use ($search) {
                $r->where('route_code', 'ILIKE', "%{$search}%")
                  ->orWhere('route_name', 'ILIKE', "%{$search}%");
            });

            $q->orWhereHas('customer', function ($c) use ($search) {
                $c->where('osa_code', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        });
    }

    $query->orderBy('id', 'DESC');

    return $query->paginate($perPage);
}

public function getByUuid(string $uuid, array $filters = [])
{
    $query = Adjustment::with([
        'warehouse:id,warehouse_code,warehouse_name',
        'route:id,route_code,route_name',
        'customer:id,osa_code,name'
    ]);

    if (!empty($filters['warehouse_id'])) {
        $query->where('warehouse_id', $filters['warehouse_id']);
    }

    if (!empty($filters['osa_code'])) {
        $query->where('osa_code', $filters['osa_code']);
    }

    if (!empty($filters['route_id'])) {
        $query->where('route_id', $filters['route_id']);
    }

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        $query->whereBetween('created_at', [
            $filters['from_date'],
            $filters['to_date']
        ]);
    }

    if (!empty($filters['min_adjustment'])) {
        $query->where('adjustment_points', '>=', $filters['min_adjustment']);
    }

    if (!empty($filters['max_adjustment'])) {
        $query->where('adjustment_points', '<=', $filters['max_adjustment']);
    }


    if (!empty($filters['search'])) {
        $search = strtolower(trim($filters['search']));

        $query->where(function ($q) use ($search) {

            $q->where('osa_code', 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(currentreward_points AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(adjustment_points AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(description AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(closing_points AS TEXT)"), 'ILIKE', "%{$search}%");

            $q->orWhereHas('warehouse', function ($w) use ($search) {
                $w->where('warehouse_code', 'ILIKE', "%{$search}%")
                  ->orWhere('warehouse_name', 'ILIKE', "%{$search}%");
            });

            $q->orWhereHas('route', function ($r) use ($search) {
                $r->where('route_code', 'ILIKE', "%{$search}%")
                  ->orWhere('route_name', 'ILIKE', "%{$search}%");
            });

            $q->orWhereHas('customer', function ($c) use ($search) {
                $c->where('osa_code', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        });
    }

    return $query->where('uuid', $uuid)->first();
}

// public function updateAdjustment(string $uuid, array $data): ?Adjustment
// {
//     return DB::transaction(function () use ($uuid, $data) {

//         $adjustment = Adjustment::where('uuid', $uuid)->first();

//         if (!$adjustment) {
//             return null;
//         }
//         $loyalty = CustomerLoyalityPoint::where('customer_id', $adjustment->customer_id)->first();

//         if (!$loyalty) {
//             throw new \Exception("Loyalty record not found for this customer.");
//         }

//         if ($adjustment->adjustment_symbol == 1) {
//             $loyalty->total_closing -= $adjustment->adjustment_points;
//         } else {
//             $loyalty->total_closing += $adjustment->adjustment_points;
//         }

//         $adjustment->update($data);

//         if ($data['adjustment_symbol'] == 1) {
//             $loyalty->total_closing += $data['adjustment_points'];
//         } else {
//             $loyalty->total_closing -= $data['adjustment_points'];
//         }

//         $loyalty->save();

//         return $adjustment->fresh();
//     });
// }

}