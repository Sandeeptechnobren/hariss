<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCustomerTiers extends Command
{
    protected $signature = 'tiers:update-daily';
    protected $description = 'Update customer tiers daily based on total purchase';

    public function handle()
    {
        $customers = DB::table('agent_customers')->get();

        foreach ($customers as $customer) {

            $totalPurchase = (int) DB::table('invoice_headers')
                ->where('customer_id', $customer->id)
                ->sum('total_amount');

            if ($totalPurchase <= 0) {
                continue;
            }

            $tier = DB::table('tbl_tiers')
                ->where('minpurchase', '<=', $totalPurchase)
                ->where('maxpurchase', '>=', $totalPurchase)
                ->first();

            if (!$tier) {
                continue;
            }

            DB::table('agent_customers')
                ->where('id', $customer->id)
                ->update([
                    'Tier' => $tier->id
                ]);
            \Log::info("Tier updated for customer {$customer->id} | Total: {$totalPurchase} | Tier: {$tier->id}");
        }

        $this->info('Daily customer tier update completed.');
    }
}
