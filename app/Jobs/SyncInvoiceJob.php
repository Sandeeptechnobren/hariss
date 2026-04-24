<?php

namespace App\Jobs;

use App\Services\V1\EfrisAPI\UraInvoiceService; // 👈 apna actual service namespace daalo
use App\Models\InvoiceHeader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoiceId;

    // 🔥 retry config
    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function handle(UraInvoiceService $service)
    {
        try {
            \Log::info('Job hit ho gaya', ['invoice_id' => $this->invoiceId]);
            // dd($this->invoiceId);
            $response = $service->syncInvoice($this->invoiceId);

            $isSuccess =
                ($response['returnCode'] ?? null) === "00" &&
                strtoupper($response['message'] ?? '') === "SUCCESS";

            if (!$isSuccess) {
                throw new \Exception($response['message'] ?? 'EFRIS sync failed');
            }

            // ✅ success update
            InvoiceHeader::where('id', $this->invoiceId)->update([
                'is_synced'   => true,
                'sync_message' => $response['message'] ?? null,
                'synced_at'   => now()
            ]);

            \Log::info('Invoice Sync Success', [
                'invoice_id' => $this->invoiceId,
                'response' => $response
            ]);
        } catch (\Throwable $e) {

            // ❌ fail update
            InvoiceHeader::where('id', $this->invoiceId)->update([
                'is_synced'   => false,
                'sync_message' => $e->getMessage()
            ]);

            \Log::error('Invoice Sync Job Failed', [
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage()
            ]);

            throw $e; // retry trigger
        }
    }
}
