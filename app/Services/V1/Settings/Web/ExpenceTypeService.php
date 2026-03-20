<?php

namespace App\Services\V1\Settings\Web;

use App\Models\ExpenceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;
use Exception;

class ExpenceTypeService
{
    /**
     * Get all expense types with filters and pagination.
     */
    public function getAll(int $perPage = 50, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = ExpenceType::query()->latest();

            foreach ($filters as $field => $value) {
                if ($value === '' || $value === null) continue;

                if (in_array($field, ['osa_code', 'name'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                }
                elseif ($field === 'status') {
                    $query->where($field, (int) $value);
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch expense types", [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Something went wrong while fetching expense types, please try again.");
        }
    }

    /**
     * Generate unique osa_code for expense types.
     */
    private function generateOsaCode(): string
    {
        do {
            $last = ExpenceType::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'EXT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (ExpenceType::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    /**
     * Create new expense type.
     */
    public function create(array $data): ExpenceType
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $data['osa_code'] ?? $this->generateOsaCode(),
                'status' => $data['status'] ?? true,
            ]);

            $expenceType = ExpenceType::create($data);

            DB::commit();
            return $expenceType;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Expense type creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => Auth::id(),
            ]);

            throw new Exception("Something went wrong while creating expense type.", 0, $e);
        }
    }

    /**
     * Find a single expense type by UUID.
     */
    public function findByUuid(string $uuid): ?ExpenceType
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return ExpenceType::where('uuid', $uuid)->first();
    }

    /**
     * Update expense type by UUID.
     */
    public function updateByUuid(string $uuid, array $data): ExpenceType
    {
        $expenceType = $this->findByUuid($uuid);
        if (!$expenceType) {
            throw new Exception("Expense type not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $expenceType->update($data);

            DB::commit();
            return $expenceType->fresh();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Expense type update failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'payload' => $data,
            ]);

            throw new Exception("Something went wrong while updating expense type.", 0, $e);
        }
    }

    /**
     * Soft delete expense type by UUID.
     */
    public function deleteByUuid(string $uuid): bool
    {
        $expenceType = $this->findByUuid($uuid);
        if (!$expenceType) {
            throw new Exception("Expense type not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $expenceType->delete();
            DB::commit();
            return true;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Expense type delete failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            throw new Exception("Something went wrong while deleting expense type.", 0, $e);
        }
    }
}
