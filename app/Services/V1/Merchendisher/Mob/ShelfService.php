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
public function storeAll(array $data)
{
    DB::beginTransaction();
    try {
        $response = [];
        if (!empty($data['damage'])) {
            $response['damage'] = [];
            foreach ($data['damage'] as $item) {
                $response['damage'][] = Damage::create($item);
            }
        }
        if (!empty($data['expiry'])) {
            $response['expiry'] = [];
            foreach ($data['expiry'] as $item) {
                $response['expiry'][] = ExpiryShelfItem::create($item);
            }
        }
        if (!empty($data['view_stock'])) {
            $response['view_stock'] = [];
            foreach ($data['view_stock'] as $item) {
                $response['view_stock'][] = ViewStockPost::create($item);
            }
        }
        DB::commit();
        return $response;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
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
            ->whereRaw("
        ? = ANY(
            string_to_array(
                regexp_replace(merchendisher_id, '[^0-9,]', '', 'g'),
                ','
            )::int[]
        )
    ", [$merchandiserId])
        ->get();
    return [
        'shelves' => $shelves,
        'planograms' => $planograms,
    ];
}
}