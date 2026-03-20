<?php

namespace App\Services\V1\Assets\Web;

use App\Models\Vendor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Error;
use Exception;

class VendorService
{
    public function all(int $perPage = 10, array $filters = [])
    {
        $query = Vendor::latest();
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['code', 'name', 'contact', 'email', 'status'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }

    public function generateCode(): string
    {
        do {
            $last = Vendor::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->code)) + 1 : 1;
            $code = 'VN' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (Vendor::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function create(array $data): Vendor
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'code'       => $this->generateCode(),
                'uuid'       => $data['uuid'] ?? Str::uuid()->toString(),
            ]);

            $vendor = Vendor::create($data);

            DB::commit();
            return $vendor;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("Vendor creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    public function findByUuid(string $uuid): ?Vendor
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return Vendor::where('uuid', $uuid)->first();
    }

    public function updateByUuid(string $uuid, array $data): Vendor
    {
        $vendor = $this->findByUuid($uuid);
        if (!$vendor) {
            throw new \Exception("Vendor not found or invalid UUID: {$uuid}");
        }
        
        DB::beginTransaction();
        
        try {
            $vendor->fill($data);
            $vendor->save();

            DB::commit();
            return $vendor;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("Vendor update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    // Delete vendor by UUID
    public function deleteByUuid(string $uuid): void
    {
        $vendor = $this->findByUuid($uuid);
        if (!$vendor) {
            throw new \Exception("Vendor not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $vendor->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error
                ? "Server error occurred."
                : "Something went wrong, please try again.";

            Log::error("Vendor delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
}
