<?php

namespace App\Services\V1\Merchendisher\Mob;

use App\Models\Damage;
use App\Models\ExpiryShelfItem;
use App\Models\Planogram;
use App\Models\Shelve;
use App\Models\ViewStockPost;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class ShelfService
{
public function create(array $data)
{
    try {
        DB::beginTransaction();
        $damage = Damage::create($data);
        DB::commit();
        return $damage;
    } catch (\Exception $e) {
        DB::rollBack();
        throw new \Exception("Failed to create damage stock: " . $e->getMessage());
    }
}
public function expirycreate(array $data)
    {
        try {
            DB::beginTransaction();
            $item = ExpiryShelfItem::create($data);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to create expiry shelf item: " . $e->getMessage());
        }
    }
public function viewstock(array $data)
    {
        try {
            DB::beginTransaction();
            $item = ViewStockPost::create($data);
            DB::commit();
            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to create view stock post: " . $e->getMessage());
        }
    }
public function getShelfDataByMerchandiser($merchandiserId)
{
    $merchandiserId = (int) $merchandiserId;
    $today = Carbon::today();
    $shelves = Shelve::with(['shelfitem' => function ($q) {
            $q->whereNull('deleted_at');
        }])
        ->whereNull('deleted_at')
        ->whereDate('valid_to', '>=', $today)
        ->whereRaw("merchendiser_ids::jsonb @> ?", [json_encode([$merchandiserId])])
        ->get();
    $planograms = Planogram::whereNull('deleted_at')
        ->whereDate('valid_to', '>=', $today)
        ->whereRaw("(',' || merchendisher_id || ',') LIKE ?", ["%,{$merchandiserId},%"])
        ->get();

    return [
        'shelves' => $shelves,
        'planograms' => $planograms,
    ];
}
}