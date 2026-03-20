<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Tier;
use Illuminate\Support\Facades\DB;
use Exception;

class TierService
{
    public function createReward(array $data): Tier
    {
        return DB::transaction(function () use ($data) {
            return Tier::create($data);
        });
    }

    public function listTiers(array $filters = [], int $perPage = null)
    {
        $query = Tier::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['period'])) {
            $query->where('period', 'like', '%' . $filters['period'] . '%');
        }

         if (!empty($filters['minpurchase'])) {
            $query->where('minpurchase', 'like', '%' . $filters['minpurchase'] . '%');
        }

         if (!empty($filters['maxpurchase'])) {
            $query->where('maxpurchase', 'like', '%' . $filters['maxpurchase'] . '%');
        }
         if (!empty($filters['period_category'])) {
            $query->where('period_category', 'like', '%' . $filters['period_category'] . '%');
        }

         if (!empty($filters['expiray_period'])) {
            $query->where('expiray_period', 'like', '%' . $filters['expiray_period'] . '%');
        }
        if ($perPage) {
            return $query->orderBy('id', 'desc')->paginate($perPage);
        }

        return $query->orderBy('id', 'desc')->get();
    }

 public function getByUuid(string $uuid): ?Tier
    {
        return Tier::where('uuid', $uuid)->first();
    }


    public function updateTierByUuid(string $uuid, array $data): ?Tier
{
    $bank = Tier::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}

public function deleteTier(string $uuid): bool
{
    return DB::transaction(function () use ($uuid) {

        $reward = Tier::where('uuid', $uuid)->firstOrFail();
        return $reward->delete();
    });
}

public function calculateCustomerTier($customerId)
{
    $totalPurchase = (int) DB::table('invoice_headers')
        ->where('customer_id', $customerId)
        ->sum('total_amount');
    
    if ($totalPurchase <= 0) {
        throw new \Exception("No purchases found for the customer");
    }
    $tier = DB::table('tbl_tiers')
        ->where('minpurchase', '<=', $totalPurchase)
        ->where('maxpurchase', '>=', $totalPurchase)
        ->first();
    
    if (!$tier) {
        throw new \Exception("No tier found for the total purchase value");
    }

    DB::table('agent_customers')
        ->where('id', $customerId)
        ->update([
            'Tier' => $tier->id
        ]);

    return [
        'customer_id'   => $customerId,
        'total_purchase'=> $totalPurchase,
        'tier_id'       => $tier->id,
        'tier_name'     => $tier->name,
        'tier_range'    => $tier->minpurchase . ' - ' . $tier->maxpurchase
    ];
}
}