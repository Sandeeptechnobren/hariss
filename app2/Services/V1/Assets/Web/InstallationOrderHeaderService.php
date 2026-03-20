<?php

namespace App\Services\V1\Assets\Web;

use App\Models\InstallationOrderHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class InstallationOrderHeaderService
{
    public function getAll(int $perPage = 10, array $filters = [])
    {
        try {
            $query = InstallationOrderHeader::query();

            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (in_array($field, ['osa_code', 'name', 'status'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            Log::error("Failed to fetch InstallationOrderHeaders", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters,
            ]);

            throw new \Exception("Unable to fetch Installation Order Headers at this time. Please try again later.");
        }
    }


    public function generateOsaCode(): string
    {
        try {
            do {
                $last = InstallationOrderHeader::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
                $osa_code = 'IRO' . str_pad($next, 3, '0', STR_PAD_LEFT);
            } while (InstallationOrderHeader::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            Log::error("Failed to generate OSA code", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Unable to generate unique OSA code. Please try again.");
        }
    }

    public function store(array $data): InstallationOrderHeader
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $data['osa_code'] ?? $this->generateOsaCode(),
            ]);

            $record = InstallationOrderHeader::create($data);

            DB::commit();
            return $record;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Failed to create InstallationOrderHeader", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception("Failed to create Installation Order Header. Please check your data and try again.");
        }
    }

    public function findByUuid(string $uuid): ?InstallationOrderHeader
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID provided: {$uuid}");
        }

        $record = InstallationOrderHeader::where('uuid', $uuid)->first();

        if (!$record) {
            throw new \Exception("No Installation Order Header found with UUID: {$uuid}");
        }

        return $record;
    }

    public function update(string $uuid, array $data): InstallationOrderHeader
    {
        $record = $this->findByUuid($uuid);

        DB::beginTransaction();

        try {
            $record->fill($data);
            $record->save();

            DB::commit();
            return $record;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Failed to update InstallationOrderHeader", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Failed to update Installation Order Header with UUID: {$uuid}. Please try again.");
        }
    }

    /**
     * Delete record by UUID (soft delete)
     */
    public function delete(string $uuid): void
    {
        $record = $this->findByUuid($uuid);

        DB::beginTransaction();

        try {
            $record->save();
            $record->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Failed to delete InstallationOrderHeader", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Failed to delete Installation Order Header with UUID: {$uuid}. Please try again.");
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = InstallationOrderHeader::with([
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch, $searchTerm) {
                    $q->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(name) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("Installation Order Header search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search Installation Order Header: " . $e->getMessage(), 0, $e);
        }
    }
}
