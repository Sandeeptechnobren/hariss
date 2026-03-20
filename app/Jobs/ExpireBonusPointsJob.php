<?php

namespace App\Jobs;

use App\Models\BonusPoint;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExpireBonusPointsJob implements ShouldQueue
{
    use Queueable;

    public function handle()
    {
        BonusPoint::where('is_expired', 0)
            ->whereDate('expiry_date', '<', Carbon::today())
            ->update([
                'is_expired' => 1
            ]);
    }
}
