<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\UnloadDetail;
use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Models\Agent_Transaction\CapsCollectionDetail;
use App\Models\WarehouseStock;
use App\Models\Item;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\Route;
use App\Models\Salesman;
use App\Models\Warehouse;
use App\Models\ItemUOM;
use App\Helpers\CommonLocationFilter;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Helpers\DataAccessHelper;
use App\Models\Agent_Transaction\LoadDetail;
use Illuminate\Pagination\Paginator;

class UnloadHeaderService
{
    // public function store(array $data)
    //     {
    //         DB::beginTransaction();

    //         try {

    //             $osaCodeHeader = $this->generateOsaCode('SUH');
    //             $salesmanId = $data['salesman_id'];
    //             $unloadNo = $this->generateUnloadNo($salesmanId);

    //             $warehouseId = $data['warehouse_id'] ?? null;

    //             if (!$warehouseId && !empty($data['route_id'])) {
    //                 $route = Route::find($data['route_id']);
    //                 $warehouseId = $route?->warehouse_id;
    //             }

    //             if (!$warehouseId) {
    //                 throw new \Exception("warehouse_id not found.");
    //             }

    //             $header = UnloadHeader::create([
    //                 'uuid'          => Str::uuid(),
    //                 'osa_code'      => $osaCodeHeader,
    //                 'warehouse_id'  => $warehouseId,
    //                 'route_id'      => $data['route_id'] ?? null,
    //                 'salesman_id'   => $salesmanId,
    //                 'unload_no'     => $unloadNo,
    //                 'unload_date'   => $data['unload_date'] ?? null,
    //                 'unload_time'   => now()->toTimeString(),
    //                 'latitude'      => $data['latitude'] ?? null,
    //                 'longtitude'    => $data['longtitude'] ?? null,
    //                 'salesman_type' => $data['salesman_type'] ?? null,
    //                 'project_type'  => $data['project_type'] ?? null,
    //                 'unload_from'   => $data['unload_from'] ?? 'Backend',
    //                 'load_date'     => $data['load_date'] ?? null,
    //                 'status'        => 1
    //             ]);

    //             foreach ($data['details'] as $detail) {

    //                 $itemId = $detail['item_id'];
    //                 $qty    = (float) $detail['qty'];
    //                 $uomId  = $detail['uom'] ?? null;

    //                 if (!$uomId) {
    //                     throw new \Exception("UOM missing for item_id {$itemId}");
    //                 }

    //                 $itemUom = DB::table('item_uoms')
    //                     ->where('item_id', $itemId)
    //                     ->where('uom_id', $uomId)
    //                     ->first();

    //                 if (!$itemUom || !$itemUom->upc) {
    //                     throw new \Exception("Invalid UOM/UPC for item {$itemId}");
    //                 }

    //                 $convertedQty = $qty * (float) $itemUom->upc;

    //                 // 🔹 Fetch stock row
    //                 $stock = WarehouseStock::where('warehouse_id', $warehouseId)
    //                     ->where('item_id', $itemId)
    //                     ->first();

    //                 if (!$stock) {
    //                     throw new \Exception("Stock missing for item {$itemId}");
    //                 }

    //                 $newQty = $stock->qty + $convertedQty;

    //                 $stock->update([
    //                     'qty'          => $newQty,
    //                     'updated_user' => $salesmanId
    //                 ]);
    //                 UnloadDetail::create([
    //                     'uuid'      => Str::uuid(),
    //                     'osa_code'  => $this->generateOsaCode('SUD'),
    //                     'header_id' => $header->id,
    //                     'item_id'   => $itemId,
    //                     'uom'       => $uomId,
    //                     'qty'       => $qty,
    //                     'status'    => 1
    //                 ]);
    //                 LoadDetail::where('item_id', $itemId)
    //                     ->where('unload_status', 0)
    //                     ->whereIn('header_id', function ($q) use ($salesmanId) {
    //                         $q->select('id')
    //                             ->from('tbl_load_header')
    //                             ->where('salesman_id', $salesmanId);
    //                     })
    //                     ->update(['unload_status' => 1]);
    //             }
    //             $pendingDetails = LoadDetail::where('unload_status', 0)
    //                 ->whereIn('header_id', function ($q) use ($salesmanId) {
    //                     $q->select('id')
    //                         ->from('tbl_load_header')
    //                         ->where('salesman_id', $salesmanId);
    //                 })
    //                 ->count();
    //             if ($pendingDetails === 0) {
    //                 LoadHeader::where('salesman_id', $salesmanId)
    //                     ->update([
    //                         'status'       => 1, 
    //                     ]);
    //             }
    //             DB::commit();
    //             return $header->load('details');
    //         } catch (\Throwable $e) {
    //             DB::rollBack();
    //             Log::error('Unload creation failed', [
    //                 'error' => $e->getMessage(),
    //                 'trace' => $e->getTraceAsString()
    //             ]);
    //             throw new \Exception("Unload creation failed: " . $e->getMessage());
    //         }
    //     }
    public function store(array $data)
    {
        DB::beginTransaction();

        try {

            $osaCodeHeader = $this->generateOsaCode('SUH');
            $salesmanId = $data['salesman_id'];
            $unloadNo = $this->generateUnloadNo($salesmanId);

            $warehouseId = $data['warehouse_id'] ?? null;

            if (!$warehouseId && !empty($data['route_id'])) {
                $route = Route::find($data['route_id']);
                $warehouseId = $route?->warehouse_id;
            }

            if (!$warehouseId) {
                throw new \Exception("warehouse_id not found.");
            }

            $header = UnloadHeader::create([
                'uuid' => Str::uuid(),
                'osa_code' => $osaCodeHeader,
                'warehouse_id' => $warehouseId,
                'route_id' => $data['route_id'] ?? null,
                'salesman_id' => $salesmanId,
                'unload_no' => $unloadNo,
                'unload_date' => $data['unload_date'] ?? null,
                'unload_time' => now()->toTimeString(),
                'latitude' => $data['latitude'] ?? null,
                'longtitude' => $data['longtitude'] ?? null,
                'salesman_type' => $data['salesman_type'] ?? null,
                'project_type' => $data['project_type'] ?? null,
                'unload_from' => $data['unload_from'] ?? 'Backend',
                'load_date' => $data['load_date'] ?? null,
                'status' => 1
            ]);

            foreach ($data['details'] as $detail) {

                $itemId = $detail['item_id'];
                $qty = (float) $detail['qty'];
                $uomId = $detail['uom'] ?? null;

                if (!$uomId) {
                    throw new \Exception("UOM missing for item_id {$itemId}");
                }

                $itemUom = DB::table('item_uoms')
                    ->where('item_id', $itemId)
                    ->where('uom_id', $uomId)
                    ->first();

                if (!$itemUom || !$itemUom->upc) {
                    throw new \Exception("Invalid UOM/UPC for item {$itemId}");
                }

                // $convertedQty = $qty * (float) $itemUom->upc;
                $convertedQty = $qty;

                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('item_id', $itemId)
                    ->first();

                if (!$stock) {
                    throw new \Exception("Stock missing for item {$itemId}");
                }

                $stock->update([
                    'qty' => $stock->qty + $convertedQty,
                    'updated_user' => $salesmanId
                ]);

                UnloadDetail::create([
                    'uuid' => Str::uuid(),
                    'osa_code' => $this->generateOsaCode('SUD'),
                    'header_id' => $header->id,
                    'item_id' => $itemId,
                    'uom' => $uomId,
                    'qty' => $qty,
                    'status' => 1
                ]);

                LoadDetail::where('item_id', $itemId)
                    ->where('unload_status', 0)
                    ->whereIn('header_id', function ($q) use ($salesmanId) {
                        $q->select('id')
                            ->from('tbl_load_header')
                            ->where('salesman_id', $salesmanId);
                    })
                    ->update(['unload_status' => 1]);
            }

            $pendingDetails = LoadDetail::where('unload_status', 0)
                ->whereIn('header_id', function ($q) use ($salesmanId) {
                    $q->select('id')
                        ->from('tbl_load_header')
                        ->where('salesman_id', $salesmanId);
                })
                ->count();

            if ($pendingDetails === 0) {
                LoadHeader::where('salesman_id', $salesmanId)
                    ->update(['status' => 1]);
            }

            DB::commit();

            /**
             * ==================================================
             * 🚀 APPLY APPROVAL WORKFLOW (SAVED PATTERN)
             * ==================================================
             */
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Unload_Header')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id' => $workflow->workflow_id,
                        'process_type' => 'Unload_Header',
                        'process_id' => $header->id
                    ]);
            }

            return $header->load('details');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Unload creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Unload creation failed: " . $e->getMessage());
        }
    }

    private function generateUnloadNo($salesmanId)
    {
        $salesman = Salesman::find($salesmanId);
        if (!$salesman || !$salesman->osa_code) {
            throw new \Exception('Salesman code not found for ID ' . $salesmanId);
        }

        $salesmanCode = $salesman->osa_code;

        $lastUnload = UnloadHeader::where('unload_no', 'LIKE', "{$salesmanCode}%")
            ->orderByDesc('id')
            ->first();

        if ($lastUnload && preg_match('/' . preg_quote($salesmanCode, '/') . '(\d+)$/', $lastUnload->unload_no, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        return sprintf('%s%04d', $salesmanCode, $nextNumber);
    }

    private function generateOsaCode(string $prefix): string
    {
        $model = $prefix === 'SUH' ? new UnloadHeader() : new UnloadDetail();

        $lastRecord = $model->where('osa_code', 'LIKE', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->osa_code, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }
    // public function all($perPage = 50, $filters = [])
    //     {
    //         try {
    //             // $user = auth()->user();
    //             $query = UnloadHeader::with(['details', 'salesman', 'warehouse', 'route'])->latest();
    //             // $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //             $query = $this->applyFilters($query, $filters);

    //             return $query->paginate($perPage);
    //         } catch (Throwable $e) {
    //             throw new \Exception("Failed to fetch unload headers: " . $e->getMessage());
    //         }
    //     }

    public function all($perPage = 50, $filters = [])
    {
        try {
            $query = UnloadHeader::with([
                'details',
                'salesman',
                'warehouse',
                'route'
            ])->latest();
            $query = $this->applyFilters($query, $filters);
            $unloads = $query->paginate($perPage);
            $unloads->getCollection()->transform(function ($unload) {
                $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Unload_Header')
                    ->where('process_id', $unload->id)
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
                        ->orderBy('step_order', 'desc')
                        ->first();
                    $unload->approval_status = $lastApprovedStep
                        ? $lastApprovedStep->message
                        : 'Initiated';
                    $unload->current_step = $currentStep?->title;
                    $unload->progress = $totalSteps > 0
                        ? ($completedSteps . '/' . $totalSteps)
                        : null;
                } else {
                    $unload->approval_status = null;
                    $unload->current_step = null;
                    $unload->progress = null;
                }
                return $unload;
            });
            return $unloads;
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch unload headers: " . $e->getMessage());
        }
    }
    private function applyFilters($query, array $filters)
    {
        // INLINE NORMALIZER (single & comma separated)
        $normalize = function ($value) {
            if (empty($value)) {
                return [];
            }

            if (is_array($value)) {
                return array_filter(array_map('intval', $value));
            }

            return array_filter(array_map(
                'intval',
                explode(',', $value)
            ));
        };

        $warehouseIds = $normalize($filters['warehouse_id'] ?? null);
        $routeIds = $normalize($filters['route_id'] ?? null);
        $regionIds = $normalize($filters['region_id'] ?? null);
        $salesmanIds = $normalize($filters['salesman_id'] ?? null);

        if (!empty($regionIds)) {
            $regionWarehouseIds = Warehouse::whereIn('region_id', $regionIds)
                ->pluck('id')
                ->toArray();

            $warehouseIds = array_unique(
                array_merge($warehouseIds, $regionWarehouseIds)
            );
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(osa_code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(unload_no) LIKE ?', ["%{$search}%"]);
            });
        }

        if (!empty($warehouseIds)) {

            $warehouseIds = is_array($warehouseIds)
                ? $warehouseIds
                : explode(',', $warehouseIds);

            $warehouseIds = array_map('intval', $warehouseIds);

            $query->whereIn('warehouse_id', $warehouseIds);
        }

        if (!empty($routeIds)) {

            $routeIds = is_array($routeIds)
                ? $routeIds
                : explode(',', $routeIds);

            $routeIds = array_map('intval', $routeIds);

            $query->whereIn('route_id', $routeIds);
        }
        if (!empty($salesmanIds)) {

            $salesmanIds = is_array($salesmanIds)
                ? $salesmanIds
                : explode(',', $salesmanIds);

            $salesmanIds = array_map('intval', $salesmanIds);

            $query->whereIn('salesman_id', $salesmanIds);
        }
        $startDate = $filters['from_date'] ?? null;
        $endDate   = $filters['to_date'] ?? null;

        if ($startDate || $endDate) {

            if ($startDate && $endDate) {
                $query->whereBetween('unload_date', [
                    $startDate . ' 00:00:00',
                    $endDate   . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->whereDate('unload_date', '>=', $startDate);
            } elseif ($endDate) {
                $query->whereDate('unload_date', '<=', $endDate);
            }
        } else {
            $query->whereDate('unload_date', Carbon::today());
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }



    public function findByUuid(string $uuid)
    {
        return UnloadHeader::with('details')->where('uuid', $uuid)->firstOrFail();
    }

    public function updateByUuid(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {
            $header = UnloadHeader::where('uuid', $uuid)->firstOrFail();

            $header->update([
                'warehouse_id' => $data['warehouse_id'] ?? $header->warehouse_id,
                'route_id' => $data['route_id'] ?? $header->route_id,
                'salesman_id' => $data['salesman_id'] ?? $header->salesman_id,
                'latitude' => $data['latitude'] ?? $header->latitude,
                'longtitude' => $data['longtitude'] ?? $header->longtitude,
                'sync_date' => now()->toDateString(),
                'sync_time' => now()->toTimeString(),
                'salesman_type' => $data['salesman_type'] ?? $header->salesman_type,
                'project_type' => $data['project_type'] ?? $header->project_type,
                'status' => $data['status'] ?? $header->status,
                'load_date' => $data['load_date'] ?? $header->load_date,
                'remarks' => $data['remarks'] ?? $header->remarks,
            ]);

            if (!empty($data['details']) && is_array($data['details'])) {
                $existingDetailUuids = $header->details()->pluck('uuid')->toArray();
                $updatedUuids = [];

                foreach ($data['details'] as $detail) {
                    if (!empty($detail['uuid'])) {
                        $existingDetail = UnloadDetail::where('uuid', $detail['uuid'])->first();

                        if ($existingDetail) {
                            $existingDetail->update([
                                'item_id' => $detail['item_id'] ?? $existingDetail->item_id,
                                'uom' => $detail['uom'] ?? $existingDetail->uom,
                                'qty' => $detail['qty'] ?? $existingDetail->qty,
                                'status' => $detail['status'] ?? $existingDetail->status,
                            ]);
                            $updatedUuids[] = $existingDetail->uuid;
                        }
                    } else {
                        $osaCodeDetail = $this->generateOsaCode('SUD');

                        $newDetail = UnloadDetail::create([
                            'uuid' => Str::uuid(),
                            'osa_code' => $osaCodeDetail,
                            'header_id' => $header->id, // ✅ FIXED HERE
                            'item_id' => $detail['item_id'],
                            'uom' => $detail['uom'],
                            'qty' => $detail['qty'],
                            'status' => $detail['status'] ?? 1,
                        ]);

                        $updatedUuids[] = $newDetail->uuid;
                    }
                }
                $detailsToDelete = array_diff($existingDetailUuids, $updatedUuids);
                if (!empty($detailsToDelete)) {
                    UnloadDetail::whereIn('uuid', $detailsToDelete)->delete();
                }
            }

            DB::commit();
            return $header->load('details');
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Unload update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Unload update failed: ' . $e->getMessage());
        }
    }


    public function deleteByUuid(string $uuid): bool
    {
        return DB::transaction(function () use ($uuid) {
            $header = UnloadHeader::where('uuid', $uuid)->firstOrFail();
            $header->details()->delete();
            return $header->delete();
        });
    }

    // public function calculateUnloadBySalesmanId(int $salesmanId)
    // {
    //     $loads = LoadHeader::with('details')->where('salesman_id', $salesmanId)->get();

    //     $unloadData = [];

    //     $invoiceTotals = InvoiceDetail::join('invoice_headers as h', 'invoice_details.header_id', '=', 'h.id')
    //         ->where('h.salesman_id', $salesmanId)
    //         ->groupBy('invoice_details.item_id')
    //         ->selectRaw('invoice_details.item_id, SUM(invoice_details.quantity) as total_invoice_qty')
    //         ->pluck('total_invoice_qty', 'item_id');

    //     $returnTotals = ReturnDetail::join('return_header as r', 'return_details.header_id', '=', 'r.id')
    //         ->where('r.salesman_id', $salesmanId)
    //         ->groupBy('return_details.item_id')
    //         ->selectRaw('return_details.item_id, SUM(return_details.item_quantity) as total_return_qty')
    //         ->pluck('total_return_qty', 'item_id');

    //     $capsTotals = CapsCollectionDetail::join('caps_collection_headers as c', 'caps_collection_details.header_id', '=', 'c.id')
    //         ->where('c.salesman_id', $salesmanId)
    //         ->groupBy('caps_collection_details.item_id')
    //         ->selectRaw('caps_collection_details.item_id, SUM(caps_collection_details.collected_quantity) as total_caps_qty')
    //         ->pluck('total_caps_qty', 'item_id');

    //     $itemIds = $loads->pluck('details.*.item_id')->flatten()->unique();
    //     $items = Item::whereIn('id', $itemIds)
    //         ->select('id', 'name', 'erp_code')
    //         ->get()
    //         ->keyBy('id'); // [item_id => Item model]
    //     foreach ($loads as $load) {
    //         foreach ($load->details as $item) {
    //             $itemId = $item->item_id;
    //             $totalLoadQty = $item->qty;
    //             $invoiceQty = $invoiceTotals[$itemId] ?? 0;
    //             $returnQty = $returnTotals[$itemId] ?? 0;
    //             $capsQty = $capsTotals[$itemId] ?? 0;

    //             $unloadQty = $totalLoadQty - $invoiceQty + $returnQty - $capsQty;

    //             $unloadData[] = [
    //                 'item_id' => $itemId,
    //                 'item_name' => $items[$itemId]->name ?? null,      // name from items table
    //                 'erp_code'  => $items[$itemId]->erp_code ?? null,  // erp_code from items table
    //                 // 'item_name' => $item->name ?? null,
    //                 'total_load' => $totalLoadQty,
    //                 'invoice_qty' => $invoiceQty,
    //                 'return_qty' => $returnQty,
    //                 'caps_collection_qty' => $capsQty,
    //                 'unload_qty' => $unloadQty,
    //             ];
    //             // dd($unloadData);
    //         }
    //     }

    //     return $unloadData;
    // }


    // public function calculateUnloadBySalesmanId(int $salesmanId, ?string $date = null)
    // {
    //     $hasDate = !empty($date);

    //     if ($hasDate) {
    //         $alreadyExists = UnloadHeader::where('salesman_id', $salesmanId)
    //             ->whereDate('created_at', $date)
    //             ->exists();

    //         if ($alreadyExists) {
    //             return [
    //                 'status'  => 'error',
    //                 'message' => 'Unload already exists for this salesman and date.'
    //             ];
    //         }
    //     }

    //     // 🔹 Load Headers + Details
    //     $loadsQuery = LoadHeader::with('details')
    //         ->where('salesman_id', $salesmanId);

    //     if ($hasDate) {
    //         $loadsQuery->whereDate('created_at', $date);
    //     }

    //     // dd($loadsQuery);
    //     $loads = $loadsQuery->get();

    //     $unloadData = [];

    //     // 🔹 Invoice Totals
    //     $invoiceQuery = InvoiceDetail::join('invoice_headers as h', 'invoice_details.header_id', '=', 'h.id')
    //         ->where('h.salesman_id', $salesmanId);

    //     if ($hasDate) {
    //         $invoiceQuery->whereDate('h.created_at', $date);
    //     }

    //     $invoiceTotals = $invoiceQuery
    //         ->groupBy('invoice_details.item_id')
    //         ->selectRaw('invoice_details.item_id, SUM(invoice_details.quantity) as total_invoice_qty')
    //         ->pluck('total_invoice_qty', 'item_id');

    //     // 🔹 Return Totals
    //     $returnQuery = ReturnDetail::join('return_header as r', 'return_details.header_id', '=', 'r.id')
    //         ->where('r.salesman_id', $salesmanId);

    //     if ($hasDate) {
    //         $returnQuery->whereDate('r.created_at', $date);
    //     }

    //     $returnTotals = $returnQuery
    //         ->groupBy('return_details.item_id')
    //         ->selectRaw('return_details.item_id, SUM(return_details.item_quantity) as total_return_qty')
    //         ->pluck('total_return_qty', 'item_id');

    //     // 🔹 Caps Collection
    //     $capsQuery = CapsCollectionDetail::join('caps_collection_headers as c', 'caps_collection_details.header_id', '=', 'c.id')
    //         ->where('c.salesman_id', $salesmanId);

    //     if ($hasDate) {
    //         $capsQuery->whereDate('c.created_at', $date);
    //     }

    //     $capsTotals = $capsQuery
    //         ->groupBy('caps_collection_details.item_id')
    //         ->selectRaw('caps_collection_details.item_id, SUM(caps_collection_details.collected_quantity) as total_caps_qty')
    //         ->pluck('total_caps_qty', 'item_id');

    //     // 🔹 Fetch item info
    //     $itemIds = $loads->pluck('details.*.item_id')->flatten()->unique();
    //     $items = Item::whereIn('id', $itemIds)
    //         ->select('id', 'name', 'erp_code')
    //         ->get()
    //         ->keyBy('id');

    //     // 🔥 Prepare Final Output
    //     foreach ($loads as $load) {
    //         foreach ($load->details as $item) {

    //             $itemId = $item->item_id;
    //             $loadQty = $item->qty;

    //             $invoiceQty = $invoiceTotals[$itemId] ?? 0;
    //             $returnQty  = $returnTotals[$itemId] ?? 0;
    //             $capsQty    = $capsTotals[$itemId] ?? 0;

    //             $unloadQty = $loadQty - $invoiceQty + $returnQty - $capsQty;

    //             $unloadData[] = [
    //                 'item_id' => $itemId,
    //                 'item_name' => $items[$itemId]->name ?? null,
    //                 'erp_code' => $items[$itemId]->erp_code ?? null,
    //                 'uom' => $item->uom,
    //                 'total_load' => $loadQty,
    //                 'invoice_qty' => $invoiceQty,
    //                 'return_qty' => $returnQty,
    //                 'caps_collection_qty' => $capsQty,
    //                 'unload_qty' => $unloadQty,
    //             ];
    //         }
    //     }

    //     return $unloadData;
    // }


    public function calculateUnloadBySalesmanId(int $salesmanId, ?string $date = null)
    {
        $hasDate = !empty($date);

        $loadsQuery = LoadHeader::with([
            'details' => function ($q) {
                $q->where('unload_status', 0);
            }
        ])->where('salesman_id', $salesmanId);

        if ($hasDate) {
            $loadsQuery->whereDate('created_at', $date);
        }

        $loads = $loadsQuery->get();

        if ($loads->isEmpty() || $loads->pluck('details')->flatten()->isEmpty()) {
            return [
                'message' => 'No pending loads available for unload.',
                'data' => []
            ];
        }

        $invoiceQuery = InvoiceDetail::join('invoice_headers as h', 'invoice_details.header_id', '=', 'h.id')
            ->where('h.salesman_id', $salesmanId);

        if ($hasDate) {
            $invoiceQuery->whereDate('h.created_at', $date);
        }

        $invoiceTotals = $invoiceQuery
            ->groupBy('invoice_details.item_id')
            ->selectRaw('invoice_details.item_id, SUM(invoice_details.quantity) as total_invoice_qty')
            ->pluck('total_invoice_qty', 'item_id');

        $returnQuery = ReturnDetail::join('return_header as r', 'return_details.header_id', '=', 'r.id')
            ->where('r.salesman_id', $salesmanId);

        if ($hasDate) {
            $returnQuery->whereDate('r.created_at', $date);
        }

        $returnTotals = $returnQuery
            ->groupBy('return_details.item_id')
            ->selectRaw('return_details.item_id, SUM(return_details.item_quantity) as total_return_qty')
            ->pluck('total_return_qty', 'item_id');


        $capsQuery = CapsCollectionDetail::join('caps_collection_headers as c', 'caps_collection_details.header_id', '=', 'c.id')
            ->where('c.salesman_id', $salesmanId);

        if ($hasDate) {
            $capsQuery->whereDate('c.created_at', $date);
        }

        $capsTotals = $capsQuery
            ->groupBy('caps_collection_details.item_id')
            ->selectRaw('caps_collection_details.item_id, SUM(caps_collection_details.collected_quantity) as total_caps_qty')
            ->pluck('total_caps_qty', 'item_id');

        $itemIds = $loads->pluck('details.*.item_id')->flatten()->unique();

        $items = Item::whereIn('id', $itemIds)
            ->select('id', 'name', 'erp_code')
            ->get()
            ->keyBy('id');

        $itemUoms = ItemUom::whereIn('item_id', $itemIds)
            ->select('item_id', 'uom_id', 'upc')
            ->get()
            ->groupBy('item_id');

        $Uoms = ItemUom::whereIn('item_id', $itemIds)
            ->where('uom_type', 'primary')
            ->select('item_id', 'uom_id')
            ->get()
            ->keyBy('item_id');
        // dd($Uoms);
        $merged = [];

        foreach ($loads as $load) {
            foreach ($load->details as $item) {

                $itemId = $item->item_id;
                $uomId = $item->uom;

                $loadQty = $item->qty;
                $invoiceQty = $invoiceTotals[$itemId] ?? 0;
                $returnQty = $returnTotals[$itemId] ?? 0;
                $capsQty = $capsTotals[$itemId] ?? 0;

                $uomRow = $itemUoms[$itemId]->where('uom_id', $uomId)->first();
                $upc = $uomRow->upc ?? 1;

                if ($uomId != 1) {
                    $loadQty *= $upc;
                }
                $primaryUomId = $Uoms[$itemId]->uom_id ?? null;
                $unloadQty = $loadQty - $invoiceQty + $returnQty - $capsQty;

                // row data
                $row = [
                    'item_id' => $itemId,
                    'item_name' => $items[$itemId]->name ?? null,
                    'erp_code' => $items[$itemId]->erp_code ?? null,

                    'uom' => $primaryUomId,
                    // 'uom_name'            => $uomRow->uom ?? null,

                    'total_load' => $loadQty,
                    'invoice_qty' => $invoiceQty,
                    'return_qty' => $returnQty,
                    'caps_collection_qty' => $capsQty,
                    'unload_qty' => $unloadQty,
                ];

                if (!isset($merged[$itemId])) {
                    $merged[$itemId] = $row;
                } else {
                    $merged[$itemId]['total_load'] += $row['total_load'];
                    $merged[$itemId]['invoice_qty'] += $row['invoice_qty'];
                    $merged[$itemId]['return_qty'] += $row['return_qty'];
                    $merged[$itemId]['caps_collection_qty'] += $row['caps_collection_qty'];
                    $merged[$itemId]['unload_qty'] += $row['unload_qty'];
                }
            }
        }

        return array_values($merged);
    }

    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        try {
            $user   = auth()->user();
            $filter = $filters['filter'] ?? [];
            if (!empty($filters['current_page'])) {
                Paginator::currentPageResolver(function () use ($filters) {
                    return (int) $filters['current_page'];
                });
            }

            $query = UnloadHeader::with([
                'details',
                'salesman',
                'warehouse',
                'route'
            ])->latest();

            // ✅ Agent-based access (same everywhere)
            $query = DataAccessHelper::filterAgentTransaction($query, $user);

            // ✅ Location filter (company / region / area / warehouse / route)
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

            // ✅ Warehouse filter
            // if (!empty($filter['warehouse_id'])) {
            //     $warehouseIds = is_array($filter['warehouse_id'])
            //         ? $filter['warehouse_id']
            //         : explode(',', $filter['warehouse_id']);

            //     $query->whereIn('warehouse_id', array_map('intval', $warehouseIds));
            // }
            if (!empty($filter['route_id'])) {
                $routeIds = is_array($filter['route_id'])
                    ? $filter['route_id']
                    : explode(',', $filter['route_id']);

                $query->whereIn('route_id', array_map('intval', $routeIds));
            }
            // ✅ Salesman filter
            if (!empty($filter['salesman_id'])) {
                $salesmanIds = is_array($filter['salesman_id'])
                    ? $filter['salesman_id']
                    : explode(',', $filter['salesman_id']);

                $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
            }

            // ✅ Date range (created_at)
            if (!empty($filter['from_date'])) {
                $query->whereDate('created_at', '>=', $filter['from_date']);
            }

            if (!empty($filter['to_date'])) {
                $query->whereDate('created_at', '<=', $filter['to_date']);
            }

            return $query->paginate($perPage);
        } catch (Throwable $e) {
            throw new \Exception("Failed to fetch unload headers: " . $e->getMessage());
        }
    }

    public function getProjectSalesmanByRoute(int $routeId)
    {
        return LoadHeader::select('salesman_id')
            ->with('salesman:id,name,osa_code')
            ->where('route_id', $routeId)
            ->whereNull('deleted_at')
            ->groupBy('salesman_id')
            ->get()
            ->pluck('salesman')
            ->filter()
            ->map(function ($salesman) {
                return $salesman->makeHidden(['route', 'route_name']);
            })
            ->values();
    }
}
