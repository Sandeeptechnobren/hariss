<?php

// namespace App\Services;

// use Illuminate\Support\Facades\DB;
// use App\Models\CodeCounter;
// use Carbon\Carbon;

// class CodeGeneratorService
// {
//     public function generateNextCode(string $prefix): string
//     {
//         $currentYear = Carbon::now()->year;
//         $currentDate = Carbon::now()->format('Ymd');
//         return DB::transaction(function () use ($prefix, $currentYear, $currentDate) {
//             $counter = CodeCounter::query()
//                 ->where('prefix', $prefix)
//                 ->where('year', $currentYear)
//                 ->lockForUpdate()
//                 ->first();
//             if (!$counter) {
//                 $counter = CodeCounter::create([
//                     'prefix' => $prefix,
//                     'year' => $currentYear,
//                     'current_value' => 1
//                 ]);
//             }
//             $nextValue = $counter->current_value;
//             $counter->current_value += 1;
//             $counter->save();
//             $paddedValue = str_pad($nextValue, 6, '0', STR_PAD_LEFT);
            
//             return "{$prefix}-{$currentDate}-{$paddedValue}";
//         });
//     }
// }

namespace App\Services\V1;

use Illuminate\Support\Facades\DB;
use App\Models\CodeCounter;
use Carbon\Carbon;

class CodeGeneratorService
{
    public function generateNextCodeAtomically(string $prefix): string
    {
        $currentYear = Carbon::now()->year;
        $currentDate = Carbon::now()->format('Ymd');
        return DB::transaction(function () use ($prefix, $currentYear, $currentDate) {
            $counter = CodeCounter::query()
            ->where('prefix', $prefix)
            ->where('year', $currentYear)
            ->lockForUpdate() 
            ->first();
            if (!$counter) {
                $counter = CodeCounter::create([
                    'prefix' => $prefix,
                    'year' => $currentYear,
                    'current_value' => 1
                ]);
            }
            $nextValue = $counter->current_value;
            $counter->current_value += 1;
            $counter->save();
            $paddedValue = str_pad($nextValue, 6, '0', STR_PAD_LEFT);
            
            return "{$prefix}{$paddedValue}";
        }); 
    }
}