<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ChillerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ChillerRequestService
{
    public function all(array $filters = [], int $perPage = 10)
    {
        $query = ChillerRequest::query();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'machine_number', 'asset_number', 'outlet_name'])) {
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
            $last = ChillerRequest::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'CR' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ChillerRequest::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): ChillerRequest
    {
        DB::beginTransaction();

        try {
            $data = array_merge($data, [
                'osa_code' => $this->generateCode(),
                'uuid'        => $data['uuid'] ?? Str::uuid()->toString(),
            ]);
            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];

            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
                    $filename = time() . '_' . $data[$col]->getClientOriginalName();
                    $data[$col]->storeAs('public/chillers', $filename);
                    $data[$col] = $filename;
                }
            }
            $chiller = ChillerRequest::create($data);
            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Chiller Request creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }

    public function findByUuid(string $uuid): ?ChillerRequest
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        return ChillerRequest::where('uuid', $uuid)->first();
    }

    public function updateByUuid(string $uuid, array $data): chillerRequest
    {
        $chiller = $this->findByUuid($uuid);
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }
        
        DB::beginTransaction();
        
        try {
            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];
            
            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
                    $filename = time() . '_' . $data[$col]->getClientOriginalName();
                    $data[$col]->storeAs('public/chillers', $filename);
                    $data[$col] = $filename;
                }
            }
            
            $chiller->fill($data);
            $chiller->save();

            DB::commit();
            return $chiller;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Chiller update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }

    public function deleteByUuid(string $uuid): void
    {
        $chillerRequest = $this->findByUuid($uuid);
        if (!$chillerRequest) {
            throw new \Exception("ChillerRequest not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $chillerRequest->deleted_user = Auth::id();
            $chillerRequest->save();
            $chillerRequest->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("ChillerRequest delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting Chiller Request: " . $e->getMessage(), 0, $e);
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = ChillerRequest::with([
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'agent:id,customer_code,business_name',
                'createdBy:id,firstname,lastname,username',
                'updatedBy:id,firstname,lastname,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(owner_name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(outlet_name) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(outlet_type) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(asset_number) LIKE ?", [$likeSearch]);
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error("ChillerRequest search failed", [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception("Failed to search Chiller Requests: " . $e->getMessage(), 0, $e);
        }
    }
}