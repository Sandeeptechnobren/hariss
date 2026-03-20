<?php

namespace App\Traits;

use App\Services\CodeGeneratorService;
use App\Models\DraftReservation;
use Illuminate\Support\Facades\DB;

trait HandlesCodeReservation 
{
    protected function getCodePrefix(string $modelName): string
    {
        return config('codes.' . $modelName) ?? 
               throw new \Exception("Code prefix not defined for model: {$modelName}");
    }
    protected function reserveNewCode(string $modelName, int $userId): string
    {
        $prefix = $this->getCodePrefix($modelName);
        $codeGenerator = app(CodeGeneratorService::class);
        $code = $codeGenerator->generateNextCode($prefix); 
        DraftReservation::create([
            'reservation_code' => $code,
            'record_type' => $prefix, 
            'user_id' => $userId,
            'expires_at' => now()->addMinutes(10)
        ]);
        return $code;
    }
    protected function finalizeCodeUsage(string $code, int $userId, \Closure $creationCallback): void
    {
        DB::transaction(function () use ($code, $userId, $creationCallback) {
            $reservation = DraftReservation::where('reservation_code', $code)
                ->where('user_id', $userId)
                ->firstOrFail();
            $creationCallback();
            $reservation->delete();
        });
    }
}