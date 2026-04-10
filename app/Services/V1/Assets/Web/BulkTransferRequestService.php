<?php

namespace App\Services\V1\Assets\Web;

use App\Models\AddChiller;
use App\Models\BulkTransferRequest;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApprovalHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BulkTransferRequestService
{
    // public function getAll(int $perPage = 20, array $filters = [])
    // {
    //     try {
    //         $query = BulkTransferRequest::query();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 if (in_array($field, ['osa_code', 'status'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 } else {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }
    //          $result->getCollection()->transform(function ($item) {
    //             return ApprovalHelper::attach($item, 'Chiller_Request');
    //         });
    //         return $query->latest()->paginate($perPage);
    //     } catch (Throwable $e) {
    //         Log::error("Failed to fetch Bulk Transfer Request records", [
    //             'error'   => $e->getMessage(),
    //             'filters' => $filters
    //         ]);
    //         throw new \Exception("Unable to fetch records at this time. Try again later.");
    //     }
    // }
    public function getAll(int $perPage = 20, array $filters = [])
    {
        try {
            $query = BulkTransferRequest::query();
            if (!empty($filters['warehouse_id'])) {

                $warehouseIds = is_array($filters['warehouse_id'])
                    ? $filters['warehouse_id']
                    : explode(',', $filters['warehouse_id']);

                $warehouseIds = array_filter(array_map('intval', $warehouseIds));

                $query->whereIn('warehouse_id', $warehouseIds);
            }
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    if (empty($value) || $field === 'warehouse_id') {
                        continue;
                    }
                    if (in_array($field, ['osa_code', 'status'])) {
                        $query->whereRaw(
                            "LOWER({$field}) LIKE ?",
                            ['%' . strtolower($value) . '%']
                        );
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
            $result = $query->latest()->paginate($perPage);
            $result->getCollection()->transform(function ($item) {
                return \App\Helpers\ApprovalHelper::attach(
                    $item,
                    'bulk_transfer'
                );
            });

            return $result;
        } catch (Throwable $e) {

            Log::error("Failed to fetch Bulk Transfer Request records", [
                'error'   => $e->getMessage(),
                'filters' => $filters
            ]);

            throw new \Exception("Unable to fetch records at this time. Try again later.");
        }
    }

    public function generateOsaCode(): string
    {
        try {
            do {
                $last = BulkTransferRequest::withTrashed()->latest('id')->first();
                $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
                $osa_code = 'BTR' . str_pad($next, 5, '0', STR_PAD_LEFT);
            } while (BulkTransferRequest::withTrashed()->where('osa_code', $osa_code)->exists());

            return $osa_code;
        } catch (Throwable $e) {
            Log::error("Failed to generate OSA code", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Unable to generate unique OSA code. Please try again.");
        }
    }
    public function store(array $data)
    {

        DB::beginTransaction();
        try {
            $data = array_merge($data, [
                'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
                'osa_code' => $data['osa_code'] ?? $this->generateOsaCode(),
            ]);
            $record = BulkTransferRequest::create($data);
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Chiller_Request')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        "workflow_id"  => $workflow->workflow_id,
                        "process_type" => "bulk_transfer",
                        "process_id"   => $record->id
                    ]);
            }
            DB::commit();
            return $record;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Failed to store Bulk Transfer Request", [
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);
            throw new \Exception("Unable to create Bulk Transfer Request. Try again.");
        }
    }
    public function findByUuid(string $uuid): BulkTransferRequest
    {
        $record = BulkTransferRequest::where('uuid', $uuid)->first();

        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }

        // return $record;
        return ApprovalHelper::attach($record, 'bulk_transfer');
    }



    /**
     * Update by UUID
     */
    public function update(string $uuid, array $data): BulkTransferRequest
    {
        $record = $this->findByUuid($uuid);

        if (!$record) {
            throw new \Exception("Record not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $userId = Auth::id();

            $updatePayload = [
                'approver_id'   => $userId,
                'approved_date' => now()->format('Y-m-d'),
                'approved_qty'  => $data['total'] ?? null,
                'status'        => 1,
                'updated_user'  => $userId,
            ];

            $record->update($updatePayload);

            DB::commit();
            return $record;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("Failed to update Bulk Transfer Request", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Failed to update record for UUID: {$uuid}");
        }
    }

    /**
     * Soft Delete using UUID
     */
    public function delete(string $uuid): bool
    {
        $record = $this->findByUuid($uuid);

        DB::beginTransaction();

        try {
            $record->deleted_user = Auth::id();
            $record->save();
            $record->delete();

            DB::commit();
            return true;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("Failed to delete Bulk Transfer Request", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Failed to delete record for UUID: {$uuid}");
        }
    }

    /**
     * Global Search
     */
    public function globalSearch(int $perPage = 20, ?string $search = null)
    {
        try {
            $query = BulkTransferRequest::query();

            if (!empty($search)) {
                $search = strtolower($search);
                $likeSearch = '%' . $search . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->whereRaw("LOWER(osa_code) LIKE ?", [$likeSearch])
                        ->orWhereRaw("LOWER(osa_code) LIKE ?", [$likeSearch]);
                });
            }

            return $query->latest()->paginate($perPage);
        } catch (Throwable $e) {

            Log::error("Bulk Transfer Request search failed", [
                'error'  => $e->getMessage(),
                'search' => $search
            ]);

            throw new \Exception("Search failed. Try again later.");
        }
    }


    public function getAvailableModelNumbers()
    {
        try {

            $records = AddChiller::with('modelNumber:id,name,code')
                ->where('is_assign', 0)
                ->where('status', 3)
                ->select('model_number')
                ->groupBy('model_number')
                ->orderBy('model_number', 'ASC')
                ->get()
                ->map(function ($row) {
                    return [
                        'model_number' => $row->model_number,
                        'id'          => $row->modelNumber->id ?? null,
                        'name'        => $row->modelNumber->name ?? null,
                        'code'        => $row->modelNumber->code ?? null,
                    ];
                });
            // dd($records);
            return $records;
        } catch (\Throwable $e) {
            // dd($e);
            Log::error("Failed to fetch model numbers", [
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Unable to fetch model numbers, please try again.");
        }
    }
    public function getByRegion(int $regionId)
    {
        try {

            $records = BulkTransferRequest::query()
                ->select('id', 'osa_code', 'warehouse_id', DB::raw('COUNT(*) as count'))
                ->where('region_id', $regionId)
                ->groupBy('id', 'warehouse_id')
                ->orderBy('id', 'ASC')
                ->get();

            return $records;
        } catch (\Throwable $e) {
            // dd($e);
            throw new \Exception("Unable to fetch bulk transfer count by region");
        }
    }

    public function getWarehouseAndChillers(int $id)
    {
        try {

            // STEP 1: Find bulk transfer + warehouse details
            $record = BulkTransferRequest::with('warehouse:id,warehouse_name,warehouse_code')
                ->where('id', $id)
                ->first();

            if (!$record) {
                throw new \Exception("Bulk transfer record not found");
            }

            $warehouseId = $record->warehouse_id;

            $chillers = AddChiller::with('modelNumber:id,name,code', 'assetsCategory:id,name,osa_code', 'brand:id,osa_code,name')
                ->where('status', 3)
                ->orderBy('model_number', 'ASC')
                ->get()
                ->map(function ($row) {
                    return [
                        'id'           => $row->id,
                        'osa_code' => $row->osa_code,
                        'serial_number' => $row->serial_number,
                        'model_number' => $row->model_number,
                        'name'         => $row->modelNumber->name ?? null,
                        'code'         => $row->modelNumber->code ?? null,
                        'assetsCategory' => $row->assetsCategory,
                        'name'         => $row->assetsCategory->name ?? null,
                        'code'         => $row->assetsCategory->osa_code ?? null,
                        'brand'         => $row->brand,
                        'name'         => $row->brand->name ?? null,
                        'code'         => $row->brand->osa_code ?? null,
                        'status'       => $row->status,
                    ];
                });

            // STEP 3: Return everything
            return [
                'warehouse' => [
                    'id'         => $record->warehouse->id ?? null,
                    'name'         => $record->warehouse->warehouse_name ?? null,
                    'code'         => $record->warehouse->warehouse_code ?? null,
                ],
                'chillers' => $chillers
            ];
        } catch (\Throwable $e) {

            // dd($e);
            throw new \Exception("Unable to fetch warehouse & chiller data: " . $e->getMessage());
        }
    }

    public function allocateAssets(array $data)
    {
        DB::beginTransaction();
        try {

            // ========== UPDATE AddChiller MODELS ==========
            AddChiller::whereIn('id', $data['checked_data'])
                ->update([
                    'status'       => 5,   // DP Stock
                    'warehouse_id' => $data['warehouse_id'] // << NEW
                ]);

            // ========== UPDATE BulkTransferRequest MODEL ==========
            $record = BulkTransferRequest::findOrFail($data['id']);

            $record->warehouse_id         = $data['warehouse_id'];
            $record->truckno         = $data['truck_no'];
            $record->trunman_name    = $data['turnmen_name'];
            $record->turnmen_contact = $data['contact'];
            $record->allocate_assets = implode(',', $data['checked_data']);
            $record->status          = 2;   // allocated
            $record->at_btr          = 1;

            $record->save();

            DB::commit();
            return $record;
        } catch (\Throwable $e) {

            DB::rollBack();
            throw new \Exception("Asset allocation failed: " . $e->getMessage());
        }
    }


    public function countBySingleModel(int $modelId)
    {
        return AddChiller::query()
            ->where('model_number', $modelId)
            ->where('status', 3)
            ->whereNull('deleted_at')
            ->count();
    }
}
