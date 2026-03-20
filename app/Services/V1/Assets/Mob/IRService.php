<?php
namespace App\Services\V1\Assets\Mob;

use App\Models\IRHeader;
use App\Models\IROHeader;
use Illuminate\Support\Facades\DB;

class IRService
{
public function getAll(int $technicianId)
{
    try {
        return IRHeader::with([
                'iroHeader',
                'iroHeader.details',
                'iroHeader.details.chillerRequest',
                'details.crf',
                'details.fridge',
                'details.fridge.assetsCategory'
            ])
            ->where('schedule_date', today())
            ->where('salesman_id', $technicianId)
            ->where('status', 1)
            ->wherenull('deleted_at')
            ->get();
    } catch (Throwable $e) {
        Log::error("IRHeader fetch failed", [
            'error' => $e->getMessage()
        ]);
        throw new \Exception("Failed to fetch IRHeader list", 0, $e);
    }
}
public function updateStatusByIrId(array $data): bool
{
    DB::beginTransaction();
    try {
        $irId   = $data['ir_id'];
        $status = $data['status'];
        $irRecord = IRHeader::find($irId);
        if (!$irRecord) {
            throw ValidationException::withMessages([
                'ir_id' => ['IR record not found.']
            ]);
        }
        $irRecord->update([
            'status'        => $status,
            'schedule_date' => $data['schedule_date'] ?? null
        ]);
        if ($irRecord->iro_id) {
            IROHeader::where('id', $irRecord->iro_id)
                ->update(['status' => $status]);
        }
        DB::commit();
        return true;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
}