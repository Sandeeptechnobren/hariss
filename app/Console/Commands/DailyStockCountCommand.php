<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\V1\EfrisAPI\DailyStockCountService;

class DailyStockCountCommand extends Command
{
    protected $signature = 'stock:generate';
    protected $description = 'Generate Daily Stock Count';

    public function handle(DailyStockCountService $service)
    {
        try {

            $result = $service->insertData();

            \Log::info('Stock Cron Summary', [
                'message' => $result['message'] ?? '',
                'success_count' => $result['success_count'] ?? 0,
                'failed_count' => $result['failed_count'] ?? 0,
                'success_ids' => $result['success_ids'] ?? [],
                'failed_ids' => $result['failed_ids'] ?? [],
            ]);

            $this->info('Cron executed successfully');
        } catch (\Exception $e) {

            \Log::error('Stock Cron Failed', [
                'error' => $e->getMessage()
            ]);

            $this->error($e->getMessage());
        }
    }
}
