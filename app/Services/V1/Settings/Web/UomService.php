<?php

namespace App\Services\V1\Settings\Web;

use App\Models\Uom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UomService
{
    /**
     * Get paginated list of all UOMs (including soft-deleted ones)
     */
    public function getAll(int $perPage = 10, array $filters = [], bool $dropdown = false)
    {
        try {
            $query = Uom::query();

            // 🔹 DROPDOWN MODE
            if ($dropdown) {
                return $query
                    ->select('id', 'name', 'osa_code')
                    ->orderBy('name', 'asc')
                    ->get();
            }

            // 🔹 NORMAL LIST MODE
            $query->withTrashed();

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['name', 'osa_code'])) {
                        $query->whereRaw(
                            "LOWER({$field}) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            // dd($e);
            Log::error("Failed to fetch UOMs", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception(
                "Failed to fetch UOM records. Please try again later."
            );
        }
    }

    // public function getAll($perPage = 10, array $filters = [])
    // {
    //     try {
    //         $query = Uom::withTrashed();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['name', 'osa_code'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Throwable $e) {
    //         Log::error("Failed to fetch UOMs", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         throw new \Exception("Failed to fetch UOM records. Please try again later.");
    //     }
    // }

    /**
     * Create a new UOM record
     */

    public function generateCode(): string
    {
        do {
            $last = Uom::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'UOM' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Uom::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $this->generateCode(),
            ]);

            $uom = Uom::create($data);
            DB::commit();

            return $uom;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to create UOM", [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to create UOM. Please try again later.',
            ];
        }
    }

    /**
     * Get UOM by UUID
     */
    public function getByUuid(string $uuid)
    {
        try {
            $uom = Uom::withTrashed()->where('uuid', $uuid)->firstOrFail();
            return $uom;
        } catch (Throwable $e) {
            Log::error("Failed to find UOM", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("UOM not found.");
        }
    }

    /**
     * Update UOM record by UUID
     */
    public function update(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $uom = Uom::withTrashed()->where('uuid', $uuid)->firstOrFail();
            $uom->update($data);
            DB::commit();

            return [
                'status'  => true,
                'message' => 'UOM updated successfully.',
                'data'    => $uom,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to update UOM", [
                'uuid' => $uuid,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to update UOM. Please try again later.',
            ];
        }
    }

    /**
     * Soft delete UOM
     */
    public function softDelete(string $uuid)
    {
        try {
            $uom = Uom::where('uuid', $uuid)->firstOrFail();
            $uom->delete();

            return [
                'status'  => true,
                'message' => 'UOM soft deleted successfully.',
            ];
        } catch (Throwable $e) {
            Log::error("Failed to soft delete UOM", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to soft delete UOM. Please try again later.',
            ];
        }
    }

    /**
     * Restore a soft-deleted UOM
     */
    public function restoreUom(string $uuid)
    {
        try {
            $uom = Uom::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $uom->restore();

            return [
                'status'  => true,
                'message' => 'UOM restored successfully.',
                'data'    => $uom,
            ];
        } catch (Throwable $e) {
            Log::error("Failed to restore UOM", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to restore UOM. Please try again later.',
            ];
        }
    }

    /**
     * Permanently delete a UOM
     */
    public function forceDeleteUom(string $uuid)
    {
        try {
            $uom = Uom::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
            $uom->forceDelete();

            return [
                'status'  => true,
                'message' => 'UOM permanently deleted.',
            ];
        } catch (Throwable $e) {
            Log::error("Failed to permanently delete UOM", [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Failed to permanently delete UOM. Please try again later.',
            ];
        }
    }
}
