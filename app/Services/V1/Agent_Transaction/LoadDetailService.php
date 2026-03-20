<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\LoadDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LoadDetailService
{
    // 🔹 Create a new Load Detail
    // public function store(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $osaCode = $this->generateOsaCode('SLD');

    //         $detail = LoadDetail::create([
    //             'uuid' => Str::uuid(),
    //             'osa_code' => $osaCode,
    //             'header_id' => $data['header_id'],
    //             'item_id' => $data['item_id'],
    //             'uom' => $data['uom'],
    //             'qty' => $data['qty'],
    //             'price' => $data['price'],
    //             'status' => $data['status'] ?? 1,
    //             'created_user' => $data['created_user'] ?? null,
    //         ]);

    //         DB::commit();
    //         return $detail;
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Load Detail creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw new \Exception('Load Detail creation failed: ' . $e->getMessage());
    //     }
    // }

    // 🔹 Fetch all Load Details with optional filters
    public function all($perPage = 50, $filters = [])
    {
        try {
            $query = LoadDetail::latest();

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception('Failed to fetch load details: ' . $e->getMessage());
        }
    }

    // 🔹 Find Load Detail by UUID
    public function findByUuid(string $uuid)
    {
        return LoadDetail::where('uuid', $uuid)->firstOrFail();
    }

    // 🔹 Update Load Detail by UUID
    public function updateByUuid(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $detail = LoadDetail::where('uuid', $uuid)->firstOrFail();

            $detail->update([
                'header_id' => $data['header_id'] ?? $detail->header_id,
                'item_id' => $data['item_id'] ?? $detail->item_id,
                'uom' => $data['uom'] ?? $detail->uom,
                'qty' => $data['qty'] ?? $detail->qty,
                'price' => $data['price'] ?? $detail->price,
                'status' => $data['status'] ?? $detail->status,
                'updated_user' => $data['updated_user'] ?? $detail->updated_user,
            ]);

            DB::commit();
            return $detail;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Load Detail update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Load Detail update failed: ' . $e->getMessage());
        }
    }

    // 🔹 Delete Load Detail by UUID
    public function deleteByUuid(string $uuid)
    {
        return DB::transaction(function () use ($uuid) {
            $detail = LoadDetail::where('uuid', $uuid)->firstOrFail();
            return $detail->delete();
        });
    }

    // 🔹 Generate unique OSA code for Load Detail
    private function generateOsaCode(string $prefix): string
    {
        $lastRecord = LoadDetail::where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }
}
