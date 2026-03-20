<?php

namespace App\Services\V1\Settings\Web;

use App\Models\BonusPoint;
use Illuminate\Support\Facades\DB;
use Exception;

class BonusService
{
    public function createBonus(array $data): BonusPoint
    {
        return DB::transaction(function () use ($data) {
            $data['expiry_date'] = now()->addYear()->toDateString();
            return BonusPoint::create($data);
        });
    }


    public function listBonus(array $filters = [], int $perPage = null)
    {
        $query = BonusPoint::query();

        if (!empty($filters['osa_code'])) {
            $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
        }

        if (!empty($filters['item_id'])) {
            $query->where('item_id', 'like', '%' . $filters['item_id'] . '%');
        }

        if (!empty($filters['volume'])) {
            $query->where('volume', 'like', '%' . $filters['volume'] . '%');
        }

        if (!empty($filters['bonus_points'])) {
            $query->where('bonus_points', 'like', '%' . $filters['bonus_points'] . '%');
        }
        
         if (!empty($filters['reward_basis'])) {
            $query->where('reward_basis', 'like', '%' . $filters['reward_basis'] . '%');
        }

        if (isset($filters['expired'])) {
        if ($filters['expired'] == 1) {
            $query->where('is_expired', 1);
        }
        elseif ($filters['expired'] == 0) {
            $query->where('is_expired', 0);
        }
    }


        return $query->orderBy('id', 'desc')->paginate($perPage ?? 50);
    }

 public function getByUuid(string $uuid): ?BonusPoint
    {
        return BonusPoint::where('uuid', $uuid)->first();
    }


    public function updateBonus(string $uuid, array $data): ?BonusPoint
{
    $bank = BonusPoint::where('uuid', $uuid)->first();

    if (!$bank) {
        return null;
    }

    $bank->update($data);
    return $bank->fresh();
}

public function deleteBonus(string $uuid): bool
{
    return DB::transaction(function () use ($uuid) {

        $reward = BonusPoint::where('uuid', $uuid)->firstOrFail();
        return $reward->delete();
    });
}
}