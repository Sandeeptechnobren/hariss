<?php
namespace App\Services\V1\Assets\Mob;

use App\Models\CallRegister;
use Illuminate\Support\Facades\DB;

class CallRegisterService
{
public function getAll(int $technicianId)
{
    try {
        return CallRegister::where('ticket_type', 'BD')
            ->where('status', 'Pending')
            ->wherenull('deleted_at')
            ->where('technician_id', $technicianId)
            ->orderBy('id', 'desc')
            ->get();
    } catch (Throwable $e) {
        Log::error("CallRegister fetch failed", [
            'error' => $e->getMessage()
        ]);
        throw new \Exception("Failed to fetch Call Register list", 0, $e);
    }
}
}