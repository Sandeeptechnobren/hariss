<?php

namespace App\Services\V1;

use Illuminate\Support\Facades\DB;
use App\Models\CodeCounter;
use Carbon\Carbon;

class CodeGeneratorService
{
    public function generateNextCodeAtomically(string $prefix): string
    {

        $year = now()->year;

        return DB::transaction(function () use ($prefix, $year) {

            $counter = CodeCounter::where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                try {
                    $counter = CodeCounter::create([
                        'prefix'        => $prefix,
                        'year'          => $year,
                        'current_value' => 1,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Another request created it first
                    $counter = CodeCounter::where('prefix', $prefix)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->first();
                }
            }

            $next = $counter->current_value;

            $counter->increment('current_value');

            // ✅ INCLUDE YEAR IN CODE
            // return sprintf(
            //     '%s%d%s',
            //     $prefix,
            //     $year,
            //     str_pad($next, 6, '0', STR_PAD_LEFT)
            // );
            $yearSum = array_sum(str_split($year));

            return sprintf(
                '%s%d%s',
                $prefix,
                $yearSum,
                str_pad($next, 6, '0', STR_PAD_LEFT)
            );
        });
    }
    // public function generateNextCodeAtomically(string $prefix): string
    // {
    //     $now = now();
    //     $year = $now->year;
    //     $datePart = $now->format('ymd'); // YYMMDD

    //     return DB::transaction(function () use ($prefix, $year, $datePart) {

    //         $counter = CodeCounter::where('prefix', $prefix)
    //             ->where('year', $year)
    //             ->lockForUpdate()
    //             ->first();

    //         if (!$counter) {
    //             try {
    //                 $counter = CodeCounter::create([
    //                     'prefix' => $prefix,
    //                     'year' => $year,
    //                     'current_value' => 1,
    //                 ]);
    //             } catch (\Illuminate\Database\QueryException $e) {
    //                 $counter = CodeCounter::where('prefix', $prefix)
    //                     ->where('year', $year)
    //                     ->lockForUpdate()
    //                     ->first();
    //             }
    //         }

    //         /*
    //         MAGIC PART ⭐
    //         We detect if the stored counter belongs to today.
    //         */

    //         $lastGenerated = $counter->updated_at?->format('ymd');

    //         if ($lastGenerated !== $datePart) {
    //             $counter->current_value = 1;
    //             $counter->save();
    //         }

    //         $next = $counter->current_value;

    //         $counter->increment('current_value');

    //         return sprintf(
    //             '%s%s%s',
    //             $prefix,
    //             $datePart,
    //             str_pad($next, 4, '0', STR_PAD_LEFT)
    //         );
    //     });
    // }

}
