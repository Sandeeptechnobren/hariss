<?php

namespace App\Services\V1\Assets\Web;

use App\Models\AddChiller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class ChillerService
{
    /**
     * List with pagination and filters
     */
    public function all(int $perPage = 10, array $filters = [])
    {
        $query = AddChiller::with('vendor');

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['fridge_code', 'serial_number', 'asset_number', 'model_number', 'country_id'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Generate unique fridge code
     */
    public function generateCode(): string
    {
        do {
            $last = AddChiller::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->fridge_code)) + 1 : 1;
            $fridge_code = 'CH' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (AddChiller::withTrashed()->where('fridge_code', $fridge_code)->exists());
        return $fridge_code;
    }

    /**
     * Create a new chiller
     */
    public function create(array $data): AddChiller
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'fridge_code' => $this->generateCode(),
                'uuid'        => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            if (isset($data['vender_details']) && is_array($data['vender_details'])) {
                $data['vender_details'] = implode(',', $data['vender_details']);
            }

            $chiller = AddChiller::create($data);

            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Chiller creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    /**
     * Find chiller by UUID (validates UUID format)
     */
    public function findByUuid(string $uuid): ?AddChiller
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return AddChiller::where('uuid', $uuid)->first();
    }

    /**
     * Update chiller by UUID
     */
    public function updateByUuid(string $uuid, array $data): AddChiller
    {
        // $chiller = AddChiller::where('uuid', $uuid)->firstOrFail();
        $chiller = $this->findByUuid($uuid);
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            if (isset($data['vender_details']) && is_array($data['vender_details'])) {
                $data['vender_details'] = implode(',', $data['vender_details']);
            }
            $chiller->fill($data);
            $chiller->save();
            // $chiller->update($data);
            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Chiller update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }

    /**
     * Delete chiller by UUID
     */
    public function deleteByUuid(string $uuid): void
    {
        $chiller = $this->findByUuid($uuid);
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $chiller->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";

            Log::error("Chiller delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception($friendlyMessage, 0, $e);
        }
    }
}
