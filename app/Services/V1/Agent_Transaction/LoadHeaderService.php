<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\LoadDetail;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\WarehouseStock;
use App\Models\Item;
use App\Models\Salesman;
use App\Models\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class LoadHeaderService
{

public function store(array $data)
    {
        DB::beginTransaction();
        try {
            if (isset($data['data']) && is_array($data['data'])) {
                $data = $data['data'];
            }
            $osaCodeHeader = $this->generateOsaCode(
                'SLH',
                $data['osa_code'] ?? null
            );
            $header = LoadHeader::create([
                'uuid'          => Str::uuid(),
                'osa_code'      => $osaCodeHeader,
                'salesman_id'   => $data['salesman_id'] ?? null,
                'route_id'      => $data['route_id'] ?? null,
                'warehouse_id'  => $data['warehouse_id'] ?? null,
                'load_date'     => $data['load_date'] ?? null,
                'remarks'       => $data['remarks'] ?? null,
                'latitude'      => $data['latitude'] ?? null,
                'longtitude'    => $data['longtitude'] ?? null,
                'salesman_type' => $data['salesman_type'] ?? null,
                'project_type'  => $data['project_type'] ?? null,
                'sync_time'     => $data['sync_time'] ?? null,
                'status'        => $data['status'] ?? 1,
            ]);
            if (!$header instanceof LoadHeader) {
                throw new \Exception('Failed to create header');
            }
            $warehouseId = $data['warehouse_id'];
            foreach ($data['details'] as $detail) {
                $itemId  = $detail['item_id'];
                $sentQty = (float) $detail['qty'];
                $uomId   = $detail['uom'];
                $item = Item::find($itemId);
                $itemName = $item ? $item->name : "Item ID: {$itemId}";
                $itemUom = DB::table('item_uoms')
                    ->where('item_id', $itemId)
                    ->where('uom_id', $uomId)
                    ->first();
                if (!$itemUom || !$itemUom->upc) {
                    DB::rollBack();
                    return [
                        'status'  => 'error',
                        'message' => "Invalid UOM / UPC for {$itemName}"
                    ];
                }
                $baseQty = $sentQty * (float) $itemUom->upc;
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->first();
                if (!$stock || $stock->qty < $baseQty) {
                    DB::rollBack();
                    return [
                        'status'  => 'error',
                        'message' => "Insufficient stock for {$itemName}"
                    ];
                }
                $stock->update([
                    'qty' => $stock->qty - $baseQty,
                    'updated_user' => $data['created_user'] ?? null
                ]);
                LoadDetail::create([
                    'uuid'      => Str::uuid(),
                    'osa_code'  => $this->generateOsaCode('SLD'),
                    'header_id' => $header->id,
                    'item_id'   => $itemId,
                    'uom'       => $uomId,
                    'qty'       => $sentQty,
                    'price'     => $detail['price'] ?? 0,
                    'status'    => $detail['status'] ?? 1,
                    'unload_status' => $detail['unload_status'] ?? 0,
                ]);
            }
            DB::commit();
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Load_Header')
                ->where('is_active', true)
                ->first();
            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Load_Header',
                        'process_id'   => $header->id
                    ]);
            }
            return $header->load([
                'details',
                'warehouse',
                'route',
                'salesman',
                'projecttype',
                'salesmantype',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'status'  => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function generateOsaCode(string $prefix, ?string $osaCode = null): string
    {
        if (!empty($osaCode)) {
            return $osaCode;
        }

        $model = $prefix === 'SLH'
            ? new LoadHeader()
            : new LoadDetail();

        $lastRecord = $model->where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }


public function all($perPage = 50, $filters = [])
    {
        try {
            $user = auth()->user();
            $query = LoadHeader::with([
                'details',
                'warehouse:id,warehouse_name,warehouse_code',
                'route:id,route_name,route_code',
                'salesman:id,name,osa_code',
                'salesmantype:id,salesman_type_name,salesman_type_code',
                'projecttype:id,name,osa_code'
            ])->latest();
            $query = DataAccessHelper::filterAgentTransaction($query, $user);
            if (!empty($filters['filter']) && is_array($filters['filter'])) {
                CommonLocationFilter::apply(
                    $query,
                    [
                        'company'   => $filters['filter']['company_id']   ?? null,
                        'region'    => $filters['filter']['region_id']    ?? null,
                        'area'      => $filters['filter']['area_id']      ?? null,
                        'warehouse' => $filters['filter']['warehouse_id'] ?? null,
                        'route'     => $filters['filter']['route_id']     ?? null,
                    ]
                );
            }
            $allowedFilters = [
                'id',
                'uuid',
                'osa_code',
                'warehouse_id',
                'route_id',
                'salesman_id',
                'is_confirmed',
                'accept_time',
                'salesman_sign',
                'latitude',
                'longtitude',
                'created_user',
                'updated_user',
                'deleted_user',
                'sync_time',
                'load_id',
                'status',
                'salesman_type',
                'project_type'
            ];

            $fromDate = $filters['from_date'] ?? null;
            $toDate   = $filters['todate'] ?? null;

            if ($fromDate || $toDate) {
                if ($fromDate && $toDate && $fromDate === $toDate) {

                    $date = $fromDate;

                    $query->where(function ($q) use ($date) {
                        $q->whereDate('created_at', $date)
                            ->orWhereDate('updated_at', $date);
                    });
                } elseif ($fromDate && $toDate) {

                    $from = $fromDate . ' 00:00:00';
                    $to   = $toDate   . ' 23:59:59';

                    $query->where(function ($q) use ($from, $to) {
                        $q->whereBetween('created_at', [$from, $to])
                            ->orWhereBetween('updated_at', [$from, $to]);
                    });
                } elseif ($fromDate) {

                    $from = $fromDate . ' 00:00:00';

                    $query->where(function ($q) use ($from) {
                        $q->where('created_at', '>=', $from)
                            ->orWhere('updated_at', '>=', $from);
                    });
                } elseif ($toDate) {

                    $to = $toDate . ' 23:59:59';

                    $query->where(function ($q) use ($to) {
                        $q->where('created_at', '<=', $to)
                            ->orWhere('updated_at', '<=', $to);
                    });
                }
            } else {
                $today = Carbon::today();

                $query->where(function ($q) use ($today) {
                    $q->whereDate('created_at', $today)
                        ->orWhereDate('updated_at', $today);
                });
            }
            if (!empty($filters['warehouse_id'])) {

                $warehouseIds = is_array($filters['warehouse_id'])
                    ? $filters['warehouse_id']
                    : explode(',', $filters['warehouse_id']);

                $warehouseIds = array_map('intval', $warehouseIds);

                $query->whereIn('warehouse_id', $warehouseIds);
            }

            if (!empty($filters['salesman_id'])) {

                $salesmanIds = is_array($filters['salesman_id'])
                    ? $filters['salesman_id']
                    : explode(',', $filters['salesman_id']);

                $salesmanIds = array_map('intval', $salesmanIds);

                $query->whereIn('salesman_id', $salesmanIds);
            }

            $loads = $query->paginate($perPage);
            $loads->getCollection()->transform(function ($load) {
                $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Load_Header')
                    ->where('process_id', $load->id)
                    ->orderBy('id', 'DESC')
                    ->first();
                if ($workflowRequest) {
                    $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                        ->orderBy('step_order')
                        ->first();
                    $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->count();
                    $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->count();
                    $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                        ->where('status', 'APPROVED')
                        ->orderBy('step_order', 'DESC')
                        ->first();
                    $load->approval_status = $lastApprovedStep
                        ? $lastApprovedStep->message
                        : 'Initiated';
                    $load->current_step = $currentStep ? $currentStep->title : null;
                    $load->progress = $totalSteps > 0
                        ? ($completedSteps . '/' . $totalSteps)
                        : null;
                } else {
                    $load->approval_status = null;
                    $load->current_step    = null;
                    $load->progress        = null;
                }
                return $load;
            });

            return $loads;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch load headers: " . $e->getMessage());
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
        $query = LoadHeader::with([
            'details',
            'warehouse:id,warehouse_name,warehouse_code',
            'route:id,route_name,route_code',
            'salesman:id,name,osa_code',
            'salesmantype:id,salesman_type_name,salesman_type_code',
            'projecttype:id,name,osa_code'
        ])->latest();
        $query = DataAccessHelper::filterAgentTransaction($query, $user);
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
        if (!empty($filter['warehouse_id'])){
            $warehouseIds = is_array($filter['warehouse_id'])? $filter['warehouse_id']: explode(',', $filter['warehouse_id']);
            $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
        }
        if (!empty($filter['salesman_id'])) {
            $salesmanIds = is_array($filter['salesman_id'])? $filter['salesman_id']: explode(',', $filter['salesman_id']);
            $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
        }
        if (!empty($filter['route_id'])) {
            $routeIds = is_array($filter['route_id'])? $filter['route_id']: explode(',', $filter['route_id']);
            $query->whereIn('route_id', array_map('intval', $routeIds));
        }
        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }
        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }
        return $query->paginate($perPage);
    }




    public function findByUuid(string $uuid)
    {
        return LoadHeader::with('details')->where('uuid', $uuid)->firstOrFail();
    }

    public function updateByUuid(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $header = LoadHeader::where('uuid', $uuid)->firstOrFail();

            $header->update([
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'salesman_id' => $data['salesman_id'] ?? $header->salesman_id,
                'is_confirmed' => $data['is_confirmed'] ?? $header->is_confirmed,
                'accept_time' => $data['accept_time'] ?? $header->accept_time,
                'salesman_sign' => $data['salesman_sign'] ?? $header->salesman_sign,
                'latitude' => $data['latitude'] ?? $header->latitude,
                'longtitude' => $data['longtitude'] ?? $header->longtitude,
                'salesman_type' => $data['salesman_type'] ?? $header->salesman_type,
                'project_type' => $data['project_type'] ?? $header->project_type,
                'status' => $data['status'] ?? $header->status,
            ]);

            if (!empty($data['details']) && is_array($data['details'])) {
                $existingDetailUuids = $header->details()->pluck('uuid')->toArray();
                $updatedUuids = [];

                foreach ($data['details'] as $detail) {
                    if (!empty($detail['uuid'])) {
                        $existingDetail = LoadDetail::where('uuid', $detail['uuid'])->first();
                        if ($existingDetail) {
                            $existingDetail->update([
                                'item_id' => $detail['item_id'],
                                'uom' => $detail['uom'],
                                'qty' => $detail['qty'],
                                'status' => $detail['status'],
                            ]);
                            $updatedUuids[] = $detail['uuid'];
                        }
                    } else {
                        $osaCodeDetail = $this->generateOsaCode('SLD');
                        $newDetail = LoadDetail::create([
                            'uuid' => Str::uuid(),
                            'osa_code' => $osaCodeDetail,
                            'header_id' => $header->id,
                            'item_id' => $detail['item_id'],
                            'uom' => $detail['uom'],
                            'qty' => $detail['qty'],
                            'status' => $detail['status'],
                        ]);
                        $updatedUuids[] = $newDetail->uuid;
                    }
                }

                $detailsToDelete = array_diff($existingDetailUuids, $updatedUuids);
                if (!empty($detailsToDelete)) {
                    LoadDetail::whereIn('uuid', $detailsToDelete)->delete();
                }
            }

            DB::commit();
            return $header->load('details');
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Load update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'code' => 500,
                'message' => 'Load update failed: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteByUuid(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $header = LoadHeader::where('uuid', $uuid)->firstOrFail();
            $header->details()->delete();
            return $header->delete();
        });
    }

    public function getSalesmanWithRoutes(
        int $salesmanTypeId,
        int $warehouseId
    ): array {

        // 🔹 Salesmen by type + warehouse
        $salesmen = Salesman::query()
            ->where('type', $salesmanTypeId)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'osa_code',
                'type',
                'warehouse_id'
            ]);

        // 🔹 Routes by warehouse (NO SoftDeletes)
        $routes = Route::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('route_name')
            ->get([
                'id',
                'route_name',
                'route_code',
                'warehouse_id'
            ]);

        return [
            'salesmen' => $salesmen,
            'routes'   => $routes,
        ];
    }
}
