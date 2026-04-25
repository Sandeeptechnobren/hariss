<?php

namespace App\Services\V1\Claim_Management\Web;

use App\Models\Claim_Management\Web\PetitClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Pagination\Paginator;
use App\Helpers\CommonLocationFilter;
use App\Helpers\DataAccessHelper;

use Throwable;

class PetitClaimService
{
    // public function create(array $data)
    // {
    //     try {
    //         return DB::transaction(function () use ($data) {
    //             if (!empty($data['claim_file']) && $data['claim_file']->isValid()) {
    //                 $filename = Str::random(20) . '.' . $data['claim_file']->getClientOriginalExtension();
    //                 $relativePath = 'petit_claims/' . $filename;
    //                 $data['claim_file']->storeAs('petit_claims', $filename, 'public');
    //                 $appUrl = rtrim(config('app.url'), '/');
    //                 $data['claim_file'] = $appUrl . '/storage/app/public/' . $relativePath;
    //             }
    //             $data['osa_code'] = $this->generateCode();
    //             return PetitClaim::create($data);
    //         });
    //     } catch (Throwable $e) {

    //         Log::error('Petit Claim Create Error: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw $e;
    //     }
    // }
    public function create(array $data)
    {
        try {

            $claim = DB::transaction(function () use ($data) {

                if (!empty($data['claim_file']) && $data['claim_file']->isValid()) {

                    $filename = Str::random(20) . '.' . $data['claim_file']->getClientOriginalExtension();

                    $relativePath = 'petit_claims/' . $filename;

                    $data['claim_file']->storeAs('petit_claims', $filename, 'public');

                    $appUrl = rtrim(config('app.url'), '/');

                    $data['claim_file'] = $appUrl . '/storage/app/public/' . $relativePath;
                }

                $data['osa_code'] = $this->generateCode();

                return PetitClaim::create($data);
            });

            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'PetitClaim')
                ->where('is_active', true)
                ->first();

            if ($workflow) {

                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'PetitClaim',
                        'process_id'   => $claim->id
                    ]);
            }

            return $claim;
        } catch (Throwable $e) {

            Log::error('Petit Claim Create Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function generateCode(): string
    {
        do {
            $last = PetitClaim::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'COMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (PetitClaim::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }
    // public function getAll(int $perPage = 50, array $filters = [])
    // {
    //     $query = PetitClaim::query();

    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }

    //     if (!empty($filters['claim_type'])) {
    //         $query->where('claim_type', $filters['claim_type']);
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     if (!empty($filters['month_range'])) {
    //         $query->where('month_range', $filters['month_range']);
    //     }

    //     if (!empty($filters['year'])) {
    //         $query->where('year', $filters['year']);
    //     }

    //     return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    // }
    public function getAll(int $perPage = 50, array $filters = [])
    {
        $user = auth()->user();
        $query = PetitClaim::query();

        if (!empty($filters['warehouse_id'])) {
            $warehouseIds = is_array($filters['warehouse_id'])
                ? $filters['warehouse_id']
                : explode(',', $filters['warehouse_id']);

            $query->whereIn('warehouse_id', $warehouseIds);
        }
        $query = DataAccessHelper::filterWarehouses($query, $user);

        if (!empty($filters['claim_type'])) {
            $query->where('claim_type', $filters['claim_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['month_range'])) {
            $query->where('month_range', $filters['month_range']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        $result = $query->orderBy('created_at', 'DESC')->paginate($perPage);
        $result->getCollection()->transform(function ($item) {
            return \App\Helpers\PetitApprovalHelper::attach($item, 'PetitClaim');
        });
            
        return $result;
    }


    public function getByUuid(string $uuid)
    {
        try {
            $claim = PetitClaim::where('uuid', $uuid)->first();

            if (!$claim) {
                throw new ModelNotFoundException("Petit claim not found for UUID: {$uuid}");
            }

            return $claim;
        } catch (ModelNotFoundException $e) {
            // specific not found error
            throw $e;
        } catch (Exception $e) {
            // generic error
            throw new Exception("Failed to fetch petit claim", 500);
        }
    }
    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $user = auth()->user();

        $filter = $filters['filter'] ?? [];

        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }

        $query = PetitClaim::with([
            'warehouse:id,warehouse_code,warehouse_name'
        ])->latest();

        // Agent access
        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // Location filter
        if (!empty($filter)) {

            $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                'company_id'   => $filter['company_id']   ?? null,
                'region_id'    => $filter['region_id']    ?? null,
                'area_id'      => $filter['area_id']      ?? null,
                'warehouse_id' => $filter['warehouse_id'] ?? null,
                'route_id'     => $filter['route_id']     ?? null,
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        // Warehouse filter
        if (!empty($filter['warehouse_id'])) {

            $warehouseIds = is_array($filter['warehouse_id'])
                ? $filter['warehouse_id']
                : explode(',', $filter['warehouse_id']);

            $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        }

        // OSA filter
        if (!empty($filter['osa_code'])) {
            $query->where('osa_code', $filter['osa_code']);
        }

        // Claim type
        if (!empty($filter['claim_type'])) {
            $query->where('claim_type', $filter['claim_type']);
        }

        // Status
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Year
        if (!empty($filter['year'])) {
            $query->where('year', $filter['year']);
        }

        // Month range
        if (!empty($filter['month_range'])) {
            $query->where('month_range', $filter['month_range']);
        }

        // Date filter
        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
