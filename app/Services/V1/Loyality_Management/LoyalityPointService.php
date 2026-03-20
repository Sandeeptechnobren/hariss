<?php

namespace App\Services\V1\Loyality_Management;

use App\Models\Loyality_Management\CustomerLoyalityPoint;
use App\Models\Loyality_Management\CustomerLoyalityActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Models\BonusPoint;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Tier;

class LoyalityPointService
{

public function createFromInvoice(InvoiceHeader $invoice)
{
    DB::beginTransaction();

    try {

        $customerId = $invoice->customer_id;

        $details = InvoiceDetail::with('item:id,rewards,volumes')
            ->where('header_id', $invoice->id)
            ->get();

        $totalItemRewards = 0;
        $quantities = [];

        foreach ($details as $detail) {
            $item = $detail->item;
            if (!$item) continue;

            $qty = $detail->quantity;

            $quantities[$detail->item_id] = 
                ($quantities[$detail->item_id] ?? 0) + $qty;

            if ($item->volumes > 0 && $qty >= $item->volumes) {
                $perVolumeReward = $item->rewards / $item->volumes;
                $totalItemRewards += ($qty * $perVolumeReward);
            }
        }

        $bonusPoints = 0;

        foreach ($quantities as $itemId => $totalQty) {
            $bonus = BonusPoint::where('item_id', $itemId)->first();

            if ($bonus && $bonus->volume > 0 && $totalQty >= $bonus->volume) {
                $bonusPoints += $bonus->bonus_points;
            }
        }

        $totalEarning = $totalItemRewards + $bonusPoints;

        $loyalty = CustomerLoyalityPoint::where('customer_id', $customerId)->first();

        $lastClosing = $loyalty->total_closing ?? 0;
        $newClosing = $lastClosing + $totalEarning;

        $tier = Tier::where('minpurchase', '<=', $newClosing)
            ->where('maxpurchase', '>=', $newClosing)
            ->orderBy('minpurchase', 'asc')
            ->first();

        $tierId = $tier?->id;

        if (!$loyalty) {

            $loyalty = CustomerLoyalityPoint::create([
                'customer_id'   => $customerId,
                'total_earning' => $totalEarning,
                'total_spend'   => 0,
                'total_closing' => $newClosing,
                'tier_id'       => $tierId, 
            ]);

        } else {

            $loyalty->update([
                'total_earning' => $loyalty->total_earning + $totalEarning,
                'total_closing' => $newClosing,
                'tier_id'       => $tierId,   
            ]);
        }

        CustomerLoyalityActivity::create([
            'header_id'        => $loyalty->id,
            'customer_id'      => $customerId,
            'activity_date'    => $invoice->invoice_date,
            'activity_type'    => 'earn',
            'invoice_id'       => $invoice->id,
            'description'      => null,
            'incooming_point'  => $totalEarning,
            'outgoing_point'   => 0,
            'adjustment_point' => 0,
            'closing_point'    => $newClosing,
        ]);

        DB::commit();

        return $loyalty->fresh();

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Loyalty Create Error: " . $e->getMessage());
        throw $e;
    }
}

// public function createFromInvoice(InvoiceHeader $invoice)
// {
//     DB::beginTransaction();
//     try {

//         $customerId = $invoice->customer_id;
//         $headerId = $invoice->id;
//         // $headers = InvoiceHeader::where('customer_id', $customerId)->pluck('id');

//         $details = InvoiceDetail::with('item:id,rewards,volumes')
//                 ->where('header_id', $headerId)
//                 ->get();

//         $totalItemRewards = 0;

//         foreach ($details as $detail) {
//             $item = $detail->item;
//             if (!$item) continue;

//             $qty = $detail->quantity;
//             $volumes = $item->volumes;
//             $rewards = $item->rewards;

//             if ($volumes > 0 && $qty >= $volumes) {
//                 $perVolumeReward = $rewards / $volumes;
//                 $totalItemRewards += ($qty * $perVolumeReward);
//             }
//         }
//         $quantities = [];
//         foreach ($details as $detail) {
//             if (!isset($quantities[$detail->item_id])) {
//                 $quantities[$detail->item_id] = 0;
//             }
//             $quantities[$detail->item_id] += $detail->quantity;
//         }

//         $bonusPoints = 0;

//         foreach ($quantities as $itemId => $totalQty) {
//             $bonus = BonusPoint::where('item_id', $itemId)->first();
//             if (!$bonus) continue;

//             if ($bonus->volume > 0 && $totalQty >= $bonus->volume) {
//                 $bonusPoints += $bonus->bonus_points;
//             }
//         }
//         $totalEarning = $totalItemRewards + $bonusPoints;
//         $loyalty = CustomerLoyalityPoint::where('customer_id', $customerId)->first();
//         $lastClosing = $loyalty ? $loyalty->total_closing : 0;

//         $newClosing = $lastClosing + $totalEarning;

//         $tier = Tier::where('minpurchase', '<=', $newClosing)
//                     ->where('maxpurchase', '>=', $newClosing)
//                     ->first();

//         $tierId = $tier ? $tier->id : null;

//         if (!$loyalty) {
//             $loyalty = CustomerLoyalityPoint::create([
//                 'customer_id'   => $customerId,
//                 'total_earning' => $totalEarning,
//                 'total_spend'   => 0,
//                 'total_closing' => $newClosing,
//                 'tier_id'       => $tier,
//             ]);
//         } else {
//             $loyalty->update([
//                 'total_earning' => $loyalty->total_earning + $totalEarning,
//                 'total_closing' => $newClosing,
//             ]);
//         }
//         CustomerLoyalityActivity::create([
//             'header_id'        => $loyalty->id,
//             'customer_id'      => $customerId,
//             'activity_date'    => $invoice->invoice_date,
//             'activity_type'    => 'earn',
//             'invoice_id'       => $invoice->id,
//             'description'      => null,
//             'incooming_point'  => $totalEarning,
//             'outgoing_point'   => 0,
//             'adjustment_point' => 0,
//             'closing_point'    => $newClosing,
//         ]);

//         DB::commit();
//         return $loyalty->fresh()->load('details');

//     } catch (\Exception $e) {
//         DB::rollBack();
//         Log::error("Loyalty Create Error: " . $e->getMessage());
//         throw $e;
//     }
// }


public function getAll(array $filters = [], int $perPage = 20)
{
    $query = CustomerLoyalityPoint::with([
        'customer:id,osa_code,name',
        'tier:id,osa_code,name',
        'details.customer:id,osa_code,name'
    ]);

    if (!empty($filters['osa_code'])) {
        $query->where('osa_code', 'ILIKE', "%{$filters['osa_code']}%");
    }

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['tier_id'])) {
        $query->where('tier_id', $filters['tier_id']);
    }

    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
        $query->whereHas('details', function ($q) use ($filters) {
            $q->whereBetween('created_at', [
                $filters['from_date'],
                $filters['to_date']
            ]);
        });
    }

    if (!empty($filters['search'])) {

        $search = strtolower(trim($filters['search']));

        $query->where(function ($q) use ($search) {

            $q->where('osa_code', 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(total_earning AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(total_spend AS TEXT)"), 'ILIKE', "%{$search}%")
              ->orWhere(DB::raw("CAST(total_closing AS TEXT)"), 'ILIKE', "%{$search}%");

            $q->orWhereHas('customer', function ($c) use ($search) {
                $c->where('osa_code', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
            $q->orWhereHas('tier', function ($t) use ($search) {
                $t->where('osa_code', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        });
    }

    return $query->orderBy('id', 'DESC')->paginate($perPage);
}


public function getByUuid(string $uuid, array $filters = [])
{
    $query = CustomerLoyalityPoint::with([
        'customer:id,osa_code,name',
        'tier:id,osa_code,name',
        'details' => function ($q) use ($filters) {

            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $q->whereBetween('activity_date', [
                    $filters['from_date'],
                    $filters['to_date']
                ]);
            }
            if (!empty($filters['activity_type'])) {
                $q->where('activity_type', strtolower($filters['activity_type']));
            }
            if (!empty($filters['detail_customer_id'])) {
                $q->where('customer_id', $filters['detail_customer_id']);
            }

            if (!empty($filters['search'])) {

                $search = strtolower(trim($filters['search']));

                $q->where(function($d) use ($search) {

                    $d->where('osa_code', 'ILIKE', "%{$search}%")
                    ->orWhere('activity_type', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhere('invoice_id', 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CAST(incooming_point AS TEXT)"), 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CAST(outgoing_point AS TEXT)"), 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CAST(adjustment_point AS TEXT)"), 'ILIKE', "%{$search}%")
                    ->orWhere(DB::raw("CAST(closing_point AS TEXT)"), 'ILIKE', "%{$search}%");
                });
            }

            $q->select(
                'id',
                'header_id', 
                'uuid',
                'osa_code',
                'customer_id',
                'activity_date',
                'activity_type',
                'invoice_id',
                'description',
                'incooming_point',
                'outgoing_point',
                'closing_point',
                'adjustment_point' 
            )->with(['customer:id,osa_code,name']);
        }
    ]);

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['tier_id'])) {
        $query->where('tier_id', $filters['tier_id']);
    }
    // if (!empty($filters['search'])) {

    //     $search = strtolower(trim($filters['search']));

    //     $query->where(function ($q) use ($search) {

    //         $q->where('osa_code', 'ILIKE', "%{$search}%")
    //           ->orWhere(DB::raw("CAST(total_earning AS TEXT)"), 'ILIKE', "%{$search}%")
    //           ->orWhere(DB::raw("CAST(total_spend AS TEXT)"), 'ILIKE', "%{$search}%")
    //           ->orWhere(DB::raw("CAST(total_closing AS TEXT)"), 'ILIKE', "%{$search}%");

    //         $q->orWhereHas('customer', function ($c) use ($search) {
    //             $c->where('osa_code', 'ILIKE', "%{$search}%")
    //               ->orWhere('name', 'ILIKE', "%{$search}%");
    //         });

    //         $q->orWhereHas('tier', function ($t) use ($search) {
    //             $t->where('osa_code', 'ILIKE', "%{$search}%")
    //               ->orWhere('name', 'ILIKE', "%{$search}%");
    //         });
    //     });
    // }

    return $query->where('uuid', $uuid)->first();
}


public function update(string $uuid, array $data)
{
    try {
        DB::beginTransaction();

        $header = CustomerLoyalityPoint::where('uuid', $uuid)->first();

        if (!$header) {
            return null;
        }

        $header->update([
            'osa_code'      => $data['osa_code'] ?? $header->osa_code,
            'customer_id'   => $data['customer_id'] ?? $header->customer_id,
            'total_earning' => $data['total_earning'] ?? $header->total_earning,
            'total_spend'   => $data['total_spend'] ?? $header->total_spend,
            'total_closing' => $data['total_closing'] ?? $header->total_closing,
            'tier_id'       => $data['tier_id'] ?? $header->tier_id,
        ]);

        if (!empty($data['details']) && is_array($data['details'])) {

            foreach ($data['details'] as $detail) {

                CustomerLoyalityActivity::create([
                    'header_id'      => $header->id,
                    'osa_code'       => $detail['osa_code'] ?? null,
                    'customer_id'    => $detail['customer_id'],
                    'activity_date'  => $detail['activity_date'],
                    'activity_type'  => $detail['activity_type'],
                    'record_id'      => $detail['record_id'],
                    'description'    => $detail['description'] ?? null,
                    'incooming_point' => $detail['incooming_point'] ?? 0,
                    'outgoing_point' => $detail['outgoing_point'] ?? 0,
                    'closing_point'  => $detail['closing_point'] ?? 0,
                ]);
            }
        }

        DB::commit();

        return $header->load(['details', 'customer', 'tier']);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('LoyalityPointService::update Error: ' . $e->getMessage());
        throw $e;
    }
}

public function deleteByUuid(string $uuid)
{
    try {
        DB::beginTransaction();

        $header = CustomerLoyalityPoint::where('uuid', $uuid)->first();

        if (!$header) {
            return false;
        }

        CustomerLoyalityActivity::where('header_id', $header->id)->delete();
        $header->delete();

        DB::commit();
        return true;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("LoyalityPointService::deleteByUuid Error: " . $e->getMessage());
        return false;
    }
}
public function getClosingByCustomerId(int $customerId)
{
    $record = CustomerLoyalityPoint::where('customer_id', $customerId)
        ->orderBy('id', 'DESC')
        ->first();

    return $record ? $record->total_closing : null;
}

}