<?php

namespace App\Services\V1\Assets\Web;

use App\Models\IRODetail;

use App\Models\ChillerRequest;
use App\Models\AddChiller;
use App\Models\IROHeader;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;

class IROHeaderService
{
    public function getAll(int $perPage = 10, array $filters = [])
    {
        try {
            $user = auth()->user();
            $query = IROHeader::with([
                'details.customer:id,osa_code,name,contact_no,district',
                'details.warehouse:id,warehouse_code,warehouse_name',
                'details.chillerRequest:id,osa_code,uuid,model_id',
                'details.chillerRequest.modelNumber:id,code,name' // 🔴 KEY PART
            ]);
            $query = DataAccessHelper::filterRoutes($query, $user);
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


    // public function getDetailCountWithHeader(array $filters = [])
    // {
    //     try {
    //         $query = IRODetail::query();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 $query->where($field, $value);
    //             }
    //         }

    //         $count = $query->count();

    //         $headerIds = $query->pluck('header_id')->unique();

    //         $headers = IROHeader::select([
    //             'id',
    //             'uuid',
    //             'osa_code',
    //             'status',
    //             'updated_user',
    //             'created_at'
    //         ])
    //             ->with([
    //                 'updatedBy:id,name',
    //                 'details' => function ($q) {
    //                     $q->with([
    //                         'warehouse:id,warehouse_code,warehouse_name',
    //                         'chillerRequest'
    //                     ]);
    //                 }
    //             ])
    //             ->whereIn('id', $headerIds)
    //             ->orderByDesc('created_at')   // 🔥 ADD THIS
    //             ->get();


    //         return [
    //             'count'   => $count,
    //             'headers' => $headers
    //         ];
    //     } catch (Throwable $e) {
    //         throw new \Exception("Unable to fetch detail count. " . $e->getMessage());
    //     }
    // }
    public function getDetailCountWithHeader(array $filters = [])
    {
        try {

            $user = auth()->user();
            $detailQuery = IRODetail::query();
            // $detailQuery = DataAccessHelper::filterWarehouses($detailQuery, $user);
            if (!empty($filters['warehouse_id'])) {

                $warehouseIds = is_array($filters['warehouse_id'])
                    ? $filters['warehouse_id']
                    : explode(',', $filters['warehouse_id']);

                $warehouseIds = array_filter(array_map('intval', $warehouseIds));

                $detailQuery->whereIn('warehouse_id', $warehouseIds);
            }

            foreach ($filters as $field => $value) {

                // ❌ skip special fields
                if (in_array($field, ['status', 'warehouse_id'])) {
                    continue;
                }

                if (!empty($value)) {
                    $detailQuery->where($field, $value);
                }
            }

            // 🔹 Header query start karo
            $headerQuery = IROHeader::select([
                'id',
                'uuid',
                'osa_code',
                'status',
                'updated_user',
                'created_at'
            ])
                ->with([
                    'updatedBy:id,name',
                    'details' => function ($q) {
                        $q->with([
                            'warehouse:id,warehouse_code,warehouse_name',
                            'chillerRequest'
                        ]);
                    }
                ]);

            // 🔹 Status filter apply karo
            if (!empty($filters['status'])) {

                $statuses = is_array($filters['status'])
                    ? $filters['status']
                    : explode(',', $filters['status']);

                $headerQuery->whereIn('status', $statuses);
            }

            // 🔹 Detail filtered header IDs lo
            $detailHeaderIds = $detailQuery->pluck('header_id')->unique();

            $headerQuery->whereIn('id', $detailHeaderIds);

            $headers = $headerQuery
                ->orderByDesc('created_at')
                ->get();

            $count = $headers->count();   // ✅ Correct count

            return [
                'count'   => $count,
                'headers' => $headers
            ];
        } catch (Throwable $e) {
            throw new \Exception("Unable to fetch detail count. " . $e->getMessage());
        }
    }


    public function generateOsaCode(): string
    {
        try {
            do {
                $last = IROHeader::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
                $osa_code = 'IRO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            } while (IROHeader::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            Log::error("Failed to generate OSA code", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Unable to generate unique OSA code. Please try again.");
        }
    }

    public function generateOsaCodeDetail(?string $requestedCode = null): string
    {
        try {

            if ($requestedCode) {
                $exists = IRODetail::withTrashed()
                    ->where('osa_code', $requestedCode)
                    ->exists();

                if ($exists) {
                    throw new \Exception("Provided OSA code already exists.");
                }

                return $requestedCode;
            }

            do {
                $last = IRODetail::withTrashed()->latest('id')->first();

                $next = $last
                    ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1
                    : 1;

                $osa_code = 'IRO-D' . str_pad($next, 3, '0', STR_PAD_LEFT);
            } while (IRODetail::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            // dd($e);
            Log::error("Failed to generate OSA code", ['error' => $e->getMessage()]);
            throw new \Exception("Unable to generate unique OSA code.");
        }
    }


    public function store(array $data): IROHeader
    {
        DB::beginTransaction();
        // dd($data);
        try {
            $data['uuid']     = $data['uuid'] ?? Str::uuid()->toString();
            $data['osa_code'] = $data['osa_code'] ?? $this->generateOsaCode();
            $osaCode = $this->generateOsaCodeDetail($request->osa_code ?? null);
            // dd($data['osa_code']);
            $header = IROHeader::create([
                'uuid'     => $data['uuid'],
                'osa_code' => $data['osa_code'],
                'status'   => $data['status'] ?? 1,
            ]);

            if (empty($data['crf_id'])) {
                throw new \Exception("crf_id is required.");
            }

            // ✅ Normalize CRF IDs
            $crfIds = is_array($data['crf_id'])
                ? $data['crf_id']
                : explode(',', $data['crf_id']);

            $crfIds = array_filter(array_map('intval', $crfIds));

            $chillerRequests = ChillerRequest::with('customer:id,name')
                ->select('id', 'warehouse_id', 'customer_id')
                ->whereIn('id', $crfIds)
                ->get();

            if ($chillerRequests->isEmpty()) {
                throw new \Exception("Invalid crf_id(s)");
            }

            foreach ($chillerRequests as $crf) {
                $header->details()->create([
                    'uuid'         => Str::uuid()->toString(),
                    'osa_code'     => $osaCode,
                    'crf_id'       => $crf->id,
                    'customer_id'        => $crf->customer_id,
                    'warehouse_id' => $crf->warehouse_id,
                    'installation_status' => 0,
                ]);
            }

            ChillerRequest::whereIn('id', $crfIds)
                ->update(['fridge_status' => 1, 'status' => 2, 'iro_id' => $header->id]);

            DB::commit();
            return $header;
        } catch (\Throwable $e) {
            // dd($e);
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }




    public function findByCrfId($id)
    {
        if (!$id) {
            throw new \Exception("Invalid CRF ID provided: {$id}");
        }

        // Fetch ALL records matching crf_id
        $records = IROHeader::where('id', $id)->get();

        if ($records->isEmpty()) {
            throw new \Exception("No Installation Order Headers found for CRF ID: {$id}");
        }

        return $records;
    }


    public function update(string $uuid, array $data): IROHeader
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
    // public function delete(string $uuid): void
    // {
    //     $record = $this->findByUuid($uuid);

    //     DB::beginTransaction();

    //     try {
    //         $record->save();
    //         $record->delete();

    //         DB::commit();
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error("Failed to delete InstallationOrderHeader", [
    //             'error' => $e->getMessage(),
    //             'uuid'  => $uuid,
    //         ]);

    //         throw new \Exception("Failed to delete Installation Order Header with UUID: {$uuid}. Please try again.");
    //     }
    // }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = IROHeader::with([
                'createdBy:id,username',
                'updatedBy:id,username',
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

    public function getChillers(int $headerId, int $warehouseId)
    {
        try {

            $crfIds = IRODetail::where('header_id', $headerId)
                ->pluck('crf_id')
                ->filter()
                ->toArray();
            if (empty($crfIds)) {
                return [];
            }


            $requestedModels = ChillerRequest::whereIn('id', $crfIds)
                ->whereNotNull('model')
                ->pluck('model')
                ->filter()
                ->unique()
                ->toArray();

            if (empty($requestedModels)) {
                return [];
            }

            $modelStock = AddChiller::select(
                'id',
                'serial_number',
                'model_number',
                'status',
                'is_assign',
                'assets_category',
                'model_number',
                'branding',
            )
                ->where('is_assign', 0)
                ->where('status', 5)
                ->whereIn('model_number', $requestedModels)
                ->where('warehouse_id', $warehouseId)
                ->with([
                    'assetsCategory:id,name,osa_code',
                    'modelNumber:id,name,code',
                    'brand:id,name,osa_code',
                ])
                ->orderByDesc('id')
                ->get();

            $warehouseStock = AddChiller::select(
                'id',
                'serial_number',
                'model_number',
                'status',
                'is_assign',
                'assets_category',
                'model_number',
                'branding',
            )
                ->where('is_assign', 0)
                ->where('status', 3)
                ->whereIn('model_number', $requestedModels)
                ->with([
                    'assetsCategory:id,name,osa_code',
                    'modelNumber:id,name,code',
                    'brand:id,name,osa_code',
                ])
                ->orderByDesc('id')
                ->get();

            // dd($warehouseStock);
            return $modelStock->isNotEmpty()
                ? $warehouseStock->merge($modelStock)
                : $warehouseStock;
        } catch (Throwable $e) {
            // dd($e);
            Log::error("Failed to fetch chiller list", [
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Unable to fetch chiller data.");
        }
    }


    public function reverseIroData(int $iroId): void
    {
        DB::transaction(function () use ($iroId) {

            $userId = Auth::id();

            if (!$userId) {
                throw new \Exception('Unauthorized action.');
            }

            $iroHeader = IROHeader::with('details')->findOrFail($iroId);

            foreach ($iroHeader->details as $detail) {

                ChillerRequest::where('id', $detail->crf_id)
                    ->update([
                        'fridge_status' => 0,
                        'iro_id'        => 0
                    ]);

                $detail->update([
                    'deleted_user' => $userId
                ]);

                $detail->delete(); // soft delete
            }

            $iroHeader->update([
                'deleted_user' => $userId
            ]);

            $iroHeader->delete(); // soft delete
        });
    }
}
