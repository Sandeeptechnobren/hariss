<?php

namespace App\Services\V1\Claim_Management\Web;

use App\Models\Claim_Management\Web\CompiledClaim;
use Illuminate\Support\Facades\DB;
use App\Helpers\CommonLocationFilter;
use App\Helpers\DataAccessHelper;
use App\Helpers\UserHierarchyHelper;
use Illuminate\Pagination\Paginator;
use App\Models\User;

class CompiledClaimService
{
    // public function getAll(int $perPage = 50, array $filters = [])
    // {
    //     $query = CompiledClaim::query();

    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }

    //     if (!empty($filters['claim_period'])) {
    //         $query->where('claim_period', $filters['claim_period']);
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }

    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('created_at', '>=', $filters['from_date']);
    //     }

    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('created_at', '<=', $filters['to_date']);
    //     }

    //     $query->orderBy('created_at', 'DESC');

    //     $result = $query->paginate($perPage);

    //     $result->getCollection()->transform(function ($item) {
    //         return \App\Helpers\ApprovalHelper::attach($item, 'CompiledClaim');
    //     });

    //     return $result;
    // }


    public function getAll(int $perPage = 50, array $filters = [])
    {
        $query = CompiledClaim::query();

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['claim_period'])) {
            $query->where('claim_period', $filters['claim_period']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $query->orderBy('created_at', 'DESC');

        $result = $query->paginate($perPage);

        $result->getCollection()->transform(function ($item) {

            $item->asm_username = UserHierarchyHelper::getAsmByWarehouse($item->warehouse_id);
            $item->rsm_username = UserHierarchyHelper::getRsmByWarehouse($item->warehouse_id);

            return \App\Helpers\ApprovalHelper::attach($item, 'CompiledClaim');
        });
        return $result;
    }

    // public function create(array $data)
    // {
    //     try {
    //         $data = array_merge($data, [
    //             'osa_code' => $this->generateCode(),
    //         ]);
    //         return DB::transaction(function () use ($data) {
    //             return CompiledClaim::create($data);
    //         });
    //     } catch (Throwable $e) {

    //         Log::error('Compiled Claim Create Error: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw $e;
    //     }
    // }
    public function create(array $data)
    {
        try {
            $data = array_merge($data, [
                'osa_code' => $this->generateCode(),
            ]);
            $claim = DB::transaction(function () use ($data) {
                return CompiledClaim::create($data);
            });
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'CompiledClaim')
                ->where('is_active', true)
                ->first();
            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'CompiledClaim',
                        'process_id'   => $claim->id
                    ]);
            }
            return $claim;
        } catch (Throwable $e) {
            Log::error('Compiled Claim Create Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function generateCode(): string
    {
        do {
            $last = CompiledClaim::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'COMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (CompiledClaim::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }


    public function getAddCompiledData(array $filters, int $perPage = 50)
    {
        $from = $filters['from_date'];
        $to = $filters['to_date'];

        $warehouseIds = is_array($filters['warehouse_id'])
            ? $filters['warehouse_id']
            : explode(',', $filters['warehouse_id']);

        $warehouseIdsString = implode(',', $warehouseIds);

        $where = " AND r.warehouse_id IN ($warehouseIdsString)";

        $compiledExists = DB::table('tbl_compiled_claim')
            ->whereIn('warehouse_id', $warehouseIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('start_date', [$from, $to])
                    ->orWhereBetween('end_date', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('start_date', '<=', $from)
                            ->where('end_date', '>=', $to);
                    });
            })
            ->exists();
        $notExistsCondition = "";
        if ($compiledExists) {
            $notExistsCondition = "
                AND NOT EXISTS (
                SELECT 1
                FROM tbl_compiled_claim cc
                WHERE cc.warehouse_id::integer = d.id
                AND cih.invoice_date BETWEEN cc.start_date AND cc.end_date
            )
        ";
        }
        // dd($notExistsCondition);

        // Main SQL
        $sql = "
        SELECT 
            SUM(
                CASE 
                    WHEN (cid.approved_date IS NULL OR cid.approved_date::text = '')
                        AND cid.rejected_by != 0 
                    THEN 
                        (CASE WHEN cid.uom::integer IN (2,4) THEN cid.quantity ELSE 0 END) +
                        (CASE WHEN cid.uom::integer IN (1,3) THEN cid.quantity ELSE 0 END) 
                        / NULLIF(io.upc::numeric, 0)
                    ELSE 0 
                END
            ) AS total_rejected_qty,

            SUM(
                CASE 
                    WHEN cid.approved_date IS NOT NULL 
                        AND cid.rmaction_date IS NOT NULL
                    THEN 
                        (CASE WHEN cid.uom::integer IN (2,4) THEN cid.quantity ELSE 0 END) +
                        (CASE WHEN cid.uom::integer IN (1,3) THEN cid.quantity ELSE 0 END) 
                        / NULLIF(io.upc::numeric, 0)
                    ELSE 0 
                END
            ) AS total_approved_qty,

            COUNT(CASE WHEN cid.rmaction_date IS NOT NULL THEN 1 END) AS approved_count,
            COUNT(CASE WHEN cid.rmaction_date IS NULL AND cid.rejected_by = 0 THEN 1 END) AS pending_count,

            MAX(io.price) AS price,
            d.id AS warehouse_id,
            d.warehouse_code,
            d.warehouse_name

        FROM invoice_headers cih
        LEFT JOIN invoice_details cid ON cid.header_id = cih.id
        LEFT JOIN tbl_route r ON r.id = cih.route_id
        LEFT JOIN tbl_warehouse d ON d.id = r.warehouse_id
        LEFT JOIN items pi ON pi.id = cid.item_id
        LEFT JOIN item_uoms io ON io.item_id = cid.item_id

        WHERE COALESCE(cid.promotion_id, 0) != 0
        $where
        AND cih.invoice_date >= '$from'
        AND cih.invoice_date <= '$to'
        $notExistsCondition

        GROUP BY d.id
        ORDER BY d.id DESC
    ";
        // dd($sql);
        // dd($sql);
        $data = DB::table(DB::raw("($sql) as subquery"))->paginate($perPage);

        return [
            "compiled_exists" => $compiledExists,
            "data" => $data
        ];
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

        $query = CompiledClaim::latest();

        // Agent access
        $query = DataAccessHelper::filterAgentTransaction($query, $user);

        // Location filter (company → region → area → warehouse → route)
        if (!empty($filter)) {

            $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                'company_id'   => $filter['company_id'] ?? null,
                'region_id'    => $filter['region_id'] ?? null,
                'area_id'      => $filter['area_id'] ?? null,
                'warehouse_id' => $filter['warehouse_id'] ?? null,
                'route_id'     => $filter['route_id'] ?? null,
            ]);

            if (!empty($warehouseIds)) {

                $warehouseIds = is_array($warehouseIds)
                    ? $warehouseIds
                    : explode(',', $warehouseIds);

                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }

        // OSA code filter
        if (!empty($filter['osa_code'])) {
            $query->where('osa_code', $filter['osa_code']);
        }

        // Agent filter
        if (!empty($filter['agent_id'])) {
            $query->where('agent_id', $filter['agent_id']);
        }

        // Status filter
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Claim period filter
        if (!empty($filter['claim_period'])) {
            $query->where('claim_period', $filter['claim_period']);
        }

        // Month range
        if (!empty($filter['month_range'])) {
            $query->where('month_range', $filter['month_range']);
        }

        // Date range filter
        if (!empty($filter['from_date'])) {
            $query->whereDate('start_date', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('end_date', '<=', $filter['to_date']);
        }

        return $query->paginate($perPage);
    }
}
