<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\SalesmanReconsileHeader;
use App\Models\Agent_Transaction\SalesmanReconsileDetail;
use App\Models\Item;
use App\Models\Salesman;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SalesmanReconsileService
{

    public function getSalesmanItemSummary(
        int $salesmanId,
        ?string $invoiceDate = null
    ) {
        try {

            // 0️⃣ ITEM MASTER
            $itemsMaster = Item::select('id', 'name', 'erp_code')
                ->get()
                ->keyBy('id');

            // 1️⃣ LOAD
            $loadData = LoadHeader::where('salesman_id', $salesmanId)
                ->with('details')
                ->get()
                ->flatMap->details
                ->groupBy('item_id')
                ->map(fn($rows) => [
                    'load_qty' => $rows->sum('qty'),
                ]);

            // 2️⃣ UNLOAD
            $unloadData = UnloadHeader::where('salesman_id', $salesmanId)
                ->with('details')
                ->get()
                ->flatMap->details
                ->groupBy('item_id')
                ->map(fn($rows) => [
                    'unload_qty' => $rows->sum('qty'),
                ]);

            // 3️⃣ INVOICE
            $invoiceHeaders = InvoiceHeader::where('salesman_id', $salesmanId)
                ->when(
                    $invoiceDate,
                    fn($q) => $q->whereDate('invoice_date', $invoiceDate)
                )
                ->with('details')
                ->get();

            $grandTotalAmount = $invoiceHeaders->sum('total_amount');

            $invoiceData = $invoiceHeaders
                ->flatMap(function ($header) {

                    $totalQty = $header->details->sum('quantity');

                    return $header->details->map(function ($detail) use ($header, $totalQty) {
                        return [
                            'item_id'      => $detail->item_id,
                            'quantity'     => $detail->quantity,
                            'amount_share' => $totalQty > 0
                                ? ($detail->quantity / $totalQty) * $header->total_amount
                                : 0,
                        ];
                    });
                })
                ->groupBy('item_id')
                ->map(fn($rows) => [
                    'invoice_qty'  => $rows->sum('quantity'),
                    'total_amount' => round($rows->sum('amount_share'), 2),
                ]);

            // 4️⃣ MERGE
            $itemIds = $loadData->keys()
                ->merge($unloadData->keys())
                ->merge($invoiceData->keys())
                ->unique();

            $items = $itemIds->map(function ($itemId) use (
                $itemsMaster,
                $loadData,
                $unloadData,
                $invoiceData
            ) {
                $item = $itemsMaster->get($itemId);

                return [
                    'item_id'      => $itemId,
                    'item_name'    => $item?->name,
                    'erp_code'     => $item?->erp_code,
                    'load_qty'     => $loadData[$itemId]['load_qty'] ?? 0,
                    'unload_qty'   => $unloadData[$itemId]['unload_qty'] ?? 0,
                    'invoice_qty'  => $invoiceData[$itemId]['invoice_qty'] ?? 0,
                    // 'total_amount' => $invoiceData[$itemId]['total_amount'] ?? 0,
                ];
            })->values();

            return [
                'grand_total_amount' => round($grandTotalAmount, 2),
                'items'              => $items,
            ];
        } catch (Throwable $e) {

            Log::error('Salesman Item Summary Failed', [
                'salesman_id' => $salesmanId,
                'invoice_date' => $invoiceDate,
                'error'       => $e->getMessage(),
            ]);

            throw new \Exception('Unable to generate salesman item summary', 500, $e);
        }
    }

    public function list(array $filters)
    {
        try {

            $query = SalesmanReconsileHeader::query()
                ->with([
                    'details.item:id,name,erp_code'
                ])
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            /**
             * 🔹 Filters
             */
            if (!empty($filters['salesman_id'])) {
                $query->where('salesman_id', $filters['salesman_id']);
            }

            if (!empty($filters['warehouse_id'])) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }

            // 🔹 Exact reconcile date (backward compatible)
            if (!empty($filters['reconsile_date'])) {
                $query->whereDate(
                    'reconsile_date',
                    Carbon::parse($filters['reconsile_date'])->toDateString()
                );
            }

            // 🔹 Date range (NEW – FIX)
            if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                $query->whereBetween('reconsile_date', [
                    $filters['from_date'],
                    $filters['to_date'],
                ]);
            }

            if (!empty($filters['osa_code'])) {
                $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
            }

            /**
             * 🔹 Pagination
             * Accept both `limit` and `per_page`
             */
            $limit = $filters['per_page']
                ?? $filters['limit']
                ?? 50;

            return $query->paginate((int) $limit);
        } catch (Throwable $e) {

            Log::error('Salesman Reconciliation Header List Failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new \Exception(
                'Unable to fetch salesman reconciliation list',
                500,
                $e
            );
        }
    }

    // public function list(array $filters)
    // {
    //     try {

    //         $query = SalesmanReconsileHeader::query()
    //             ->with([
    //                 'details.item:id,name,erp_code'
    //             ])
    //             ->whereNull('deleted_at')
    //             ->orderByDesc('id');

    //         /**
    //          * 🔹 Filters
    //          */
    //         if (!empty($filters['salesman_id'])) {
    //             $query->where('salesman_id', $filters['salesman_id']);
    //         }

    //         if (!empty($filters['warehouse_id'])) {
    //             $query->where('warehouse_id', $filters['warehouse_id']);
    //         }

    //         if (!empty($filters['reconsile_date'])) {
    //             $query->whereDate(
    //                 'reconsile_date',
    //                 Carbon::parse($filters['reconsile_date'])->toDateString()
    //             );
    //         }

    //         if (!empty($filters['osa_code'])) {
    //             $query->where('osa_code', 'ILIKE', '%' . $filters['osa_code'] . '%');
    //         }

    //         /**
    //          * 🔹 Pagination
    //          */
    //         $limit = $filters['limit'] ?? 50;

    //         return $query->paginate($limit);
    //     } catch (Throwable $e) {

    //         Log::error('Salesman Reconciliation Header List Failed', [
    //             'filters' => $filters,
    //             'error'   => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);

    //         throw new \Exception(
    //             'Unable to fetch salesman reconciliation list',
    //             500,
    //             $e
    //         );
    //     }
    // }
    public function create(array $data)
    {
        // dd($data);
        DB::beginTransaction();

        try {

            /**
             * ============================
             * 1️⃣ BASIC DATA
             * ============================
             */
            $salesmanId   = $data['salesman_id'];
            $warehouseId  = $data['warehouse_id'] ?? null;
            $reconsileDate = Carbon::parse($data['reconsile_date'])->toDateString();

            if (!$warehouseId) {
                throw new \Exception('warehouse_id is required');
            }

            /**
             * ============================
             * 2️⃣ CHECK DUPLICATE (SALESMAN + DATE)
             * ============================
             */
            $alreadyExists = SalesmanReconsileHeader::where('salesman_id', $salesmanId)
                ->whereDate('reconsile_date', $reconsileDate)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyExists) {
                DB::commit();

                return [
                    'status'  => 'exists',
                    'message' => 'Reconciliation already exists for this salesman and date',
                ];
            }

            /**
             * ============================
             * 3️⃣ CREATE HEADER
             * ============================
             */
            $header = SalesmanReconsileHeader::create([
                'uuid'               => Str::uuid(),
                'warehouse_id'       => $warehouseId,
                'salesman_id'        => $salesmanId,
                'reconsile_date'     => $reconsileDate,
                'grand_total_amount' => $data['grand_total_amount'] ?? 0,
                'cash_amount'        => $data['cash_amount'] ?? 0,
                'credit_amount'      => $data['credit_amount'] ?? 0,
                'created_user'       => Auth::id(),
            ]);

            /**
             * ============================
             * 4️⃣ CREATE DETAILS
             * ============================
             */
            foreach ($data['items'] as $detail) {

                $itemId = $detail['item_id'];

                SalesmanReconsileDetail::create([
                    'header_id'  => $header->id,
                    'item_id'    => $itemId,
                    'load_qty'   => $detail['load_qty'] ?? 0,
                    'unload_qty' => $detail['unload_qty'] ?? 0,
                    'invoice_qty' => $detail['invoice_qty'] ?? 0,
                ]);
            }

            DB::commit();

            /**
             * ============================
             * 5️⃣ RETURN HEADER + DETAILS
             * ============================
             */
            return [
                'status'  => 'created',
                'message' => 'Salesman reconciliation created successfully',
                'data'    => $header->load('details.item'),
            ];
        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();

            Log::error('Salesman Reconciliation Create Failed', [
                'payload' => $data,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new \Exception(
                'Salesman reconciliation creation failed: ' . $e->getMessage(),
                500,
                $e
            );
        }
    }

    public function blockSalesman(int $salesmanId): Salesman
    {
        DB::beginTransaction();

        try {

            $salesman = Salesman::where('id', $salesmanId)->first();

            if (! $salesman) {
                throw new Exception('Salesman not found');
            }
            $salesman->update([
                'is_block' => 1,
            ]);

            DB::commit();
            return $salesman;
        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Salesman block failed', [
                'salesman_id' => $salesmanId,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    public function getByUuid(string $uuid)
    {
        try {

            return SalesmanReconsileHeader::with([
                'details.item:id,name,erp_code'
            ])
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
        } catch (Throwable $e) {

            Log::error('Salesman Reconsile Get By UUID Failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception(
                'Unable to fetch salesman reconciliation by uuid',
                500,
                $e
            );
        }
    }
}
