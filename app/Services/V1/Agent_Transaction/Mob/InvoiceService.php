<?php

namespace App\Services\V1\Agent_transaction\Mob;

use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\AgentCustomer;
use App\Helpers\DataAccessHelper;

class InvoiceService
{
    public function create(array $data): ?InvoiceHeader
    {
        try {
            DB::beginTransaction();
            $warehouse_data = Warehouse::where('id', $data['warehouse_id'])->first();
            $company_data = Company::where('id', $warehouse_data->company)->first();
            $currencyName = $company->selling_currency ?? null;
            $route_id = AgentCustomer::where('id', $data['customer_id'])->value('route_id');
            $header = InvoiceHeader::create([
                'invoice_code'        => $data['invoice_code'] ?? null,
                'warehouse_id'        => $data['warehouse_id'],
                'company_id'          => $warehouse_data->company,
                'currency_name'       => $company_data->selling_currency ?? null,
                'order_id'            => $data['order_id'] ?? null,
                'delivery_id'         => $data['delivery_id'] ?? null,
                'customer_id'         => $data['customer_id'],
                'route_id'            => $route_id,
                'salesman_id'         => $data['salesman_id'] ?? null,
                'latitude'            => $data['latitude'] ?? null,
                'longitude'           => $data['longitude'] ?? null,
                'invoice_mob_number'  => $data['invoice_mob_number'] ?? null,
                'ura_invoice_id'      => $data['ura_invoice_id'] ?? null,
                'ura_invoice_no'      => $data['ura_invoice_no'] ?? null,
                'ura_antifake_code'   => $data['ura_antifake_code'] ?? null,
                'ura_qr_code'         => $data['ura_qr_code'] ?? null,
                'invoice_type'        => $data['invoice_type'] ?? 1,
                'status'              => $data['status'] ?? 1,
                'invoice_date'        => $data['invoice_date'] ?? now()->toDateString(),
                'invoice_time'        => $data['invoice_time'] ?? now()->toTimeString(),
                'gross_total'         => $data['gross_total'] ?? 0,
                'vat'                 => $data['vat'] ?? 0,
                'pre_vat'             => $data['pre_vat'] ?? 0,
                'net_total'           => $data['net_total'] ?? 0,
                'promotion_id'        => $data['promotion_id'] ?? null,
                'discount_id'         => $data['discount_id'] ?? null,
                'discount'            => $data['discount'] ?? 0,
                'promotion_total'     => $data['promotion_total'] ?? 0,
                'total_amount'        => $data['total_amount'] ?? 0,
                'purchaser_name'      => $data['purchaser_name'] ?? null,
                'purchaser_contact'   => $data['purchaser_contact'] ?? null,
            ]);
            if (!empty($data['delivery_id'])) {
                AgentDeliveryHeaders::where('id', $data['delivery_id'])
                    ->update(['status' => 2]);
                    }
            if (!empty($data['order_id'])) {
                OrderHeader::where('id', $data['order_id'])
                    ->update(['order_flag' => 3]);
                    }
            if (!empty($data['details']) && is_array($data['details'])) {
                foreach ($data['details'] as $detail) {
                    InvoiceDetail::create([
                        'header_id'          => $header->id,
                        'item_id'            => $detail['item_id'],
                        'uom'                => $detail['uom'],
                        'quantity'           => $detail['quantity'] ?? 0,
                        'itemvalue'          => $detail['itemvalue'] ?? 0,
                        'vat'                => $detail['vat'] ?? 0,
                        'pre_vat'            => $detail['pre_vat'] ?? 0,
                        'net_total'          => $detail['net_total'] ?? 0,
                        'item_total'         => $detail['total'] ?? 0,
                        'promotion_id'       => $detail['promotion_id'] ?? null,
                        'approver_id'        => $detail['approver_id'] ?? null,
                        'approved_date'      => $detail['approved_date'] ?? null,
                        'rejected_by'        => $detail['rejected_by'] ?? null,
                        'rm_approver_id'     => $detail['rm_approver_id'] ?? null,
                        'rm_reject_id'       => $detail['rm_reject_id'] ?? null,
                        'rmaction_date'      => $detail['rmaction_date'] ?? null,
                        'comment_for_rejection' => $detail['comment_for_rejection'] ?? null,
                        'status'                => $detail['status'] ?? 1,
                    ]);
                }
            }
            DB::commit();
            app(\App\Services\V1\Loyality_Management\LoyalityPointService::class)
                ->createFromInvoice($header);
            app(\App\Services\V1\Settings\Web\TierService::class)
                ->calculateCustomerTier($header->customer_id);
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Invoice_Header')
                ->where('is_active', true)
                ->first();
            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Invoice_Header',
                        'process_id'   => $header->id
                    ]);
            }
            return $header->load('details');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceService::create Error: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        $query = InvoiceHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,name,osa_code',
            'salesman:id,name,osa_code',
            'details:item_id,header_id,uom,quantity,itemvalue,vat,pre_vat,net_total,item_total,promotion_id,parent,status',
        ]);
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }
        if (!empty($filters['invoice_code'])) {
            $query->where('invoice_code', 'LIKE', '%' . $filters['invoice_code'] . '%');
        }
        if (!empty($filters['from_date'])) {
            $query->whereDate('invoice_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('invoice_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $sortBy = $filters['sort_by'] ?? 'invoice_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        if ($dropdown) {
            return $query->get()->map(function ($invoice) {
                return [
                    'id'    => $invoice->id,
                    'label' => $invoice->invoice_code,
                    'value' => $invoice->id,
                ];
            });
        }
        return $query->paginate($perPage);
    }

    public function getByUuid(string $uuid)
    {
        try {
            $current = InvoiceHeader::with([
                'warehouse:id,warehouse_name,warehouse_code,warehouse_manager_contact,warehouse_email,town_village,street,landmark,address,city,tin_no',
                'route:id,route_name,route_code',
                'customer:id,name,osa_code,street',
                'salesman:id,name,osa_code',
                'order:id,order_code',
                'details' => function ($q) {
                    $q->select(
                        'id',
                        'header_id',
                        'item_id',
                        'uom',
                        'quantity',
                        'itemvalue',
                        'vat',
                        'pre_vat',
                        'net_total',
                        'item_total',
                        'promotion_id',
                        'parent',
                        'status'
                    );
                }
            ])->where('uuid', $uuid)->first();

            if (!$current) {
                return null;
            }

            $previousUuid = InvoiceHeader::where('id', '<', $current->id)
                ->orderBy('id', 'desc')
                ->value('uuid');

            $nextUuid = InvoiceHeader::where('id', '>', $current->id)
                ->orderBy('id', 'asc')
                ->value('uuid');

            return [
                'current'  => $current,
                'previous' => $previousUuid,
                'next'     => $nextUuid,
            ];
        } catch (Exception $e) {
            Log::error('InvoiceService::getByUuid Error: ' . $e->getMessage());
            return null;
        }
    }
    public function updateOrdersStatus(array $invoiceUuids, int $status): bool
    {
        return InvoiceHeader::whereIn('uuid', $invoiceUuids)
            ->update(['status' => $status]) > 0;
    }


    //     public function filterInvoiceDetails(array $filters, int $perPage = 50)
    //     {
    //         $from = $filters['from_date'];
    //         $to = $filters['to_date'];
    //         $warehouseId = $filters['warehouse_id'];
    //         $monthrange = $filters['month_range'] ?? '';

    //         // Build WHERE string
    //         $where = " AND r.warehouse_id = " . $warehouseId;

    //         // Main SQL
    //         $sql = "
    //         SELECT 
    //     MAX(pht.month_range) AS phtrange,

    //     SUM(
    //         CASE 
    //             WHEN (cid.approved_date IS NULL OR cid.approved_date::text = '') 
    //                 AND cid.rejected_by != 0 
    //             THEN 
    //                 (CASE WHEN cid.uom::integer IN (2,4) THEN cid.quantity ELSE 0 END)
    //                 +
    //                 (CASE WHEN cid.uom::integer IN (1,3) THEN cid.quantity ELSE 0 END)
    //                     / NULLIF(io.upc::numeric, 0)
    //             ELSE 0 
    //         END
    //     ) AS total_rejected_qty,

    //     SUM(
    //         CASE 
    //             WHEN cid.approved_date IS NOT NULL 
    //                 AND cid.rmaction_date IS NOT NULL
    //             THEN 
    //                 (CASE WHEN cid.uom::integer IN (2,4) THEN cid.quantity ELSE 0 END)
    //                 +
    //                 (CASE WHEN cid.uom::integer IN (1,3) THEN cid.quantity ELSE 0 END)
    //                     / NULLIF(io.upc::numeric, 0)
    //             ELSE 0 
    //         END
    //     ) AS total_approved_qty,

    //     COUNT(
    //         CASE WHEN cid.rmaction_date IS NOT NULL THEN 1 END
    //     ) AS approved_count,

    //     COUNT(
    //         CASE 
    //             WHEN cid.rmaction_date IS NULL AND cid.rejected_by = 0
    //             THEN 1 
    //         END
    //     ) AS pending_count,

    //     MAX(io.price) AS price,

    //     d.id AS warehouse_id,
    //     d.warehouse_code,
    //     d.warehouse_name,

    //     pi.name AS item_name

    // FROM invoice_headers cih
    // LEFT JOIN invoice_details cid ON cid.header_id = cih.id
    // LEFT JOIN tbl_route r ON r.id = cih.route_id
    // LEFT JOIN tbl_warehouse d ON d.id = r.warehouse_id
    // LEFT JOIN items pi ON pi.id = cid.item_id
    // LEFT JOIN item_uoms io ON io.item_id = cid.item_id
    // LEFT JOIN tbl_compiled_claim pht 
    //     ON d.id = pht.warehouse_id::INTEGER
    // LEFT JOIN uom pu ON pu.id = cid.uom

    // WHERE cid.promotion_id != 0
    //   $where
    //   AND cih.invoice_date >= '$from'
    //   AND cih.invoice_date <= '$to'
    //   AND NOT EXISTS (
    //         SELECT 1 
    //         FROM tbl_compiled_claim cc
    //         WHERE cc.warehouse_id::INTEGER = d.id
    //           AND cc.month_range = '$monthrange'
    //           AND cih.invoice_date BETWEEN cc.start_date AND cc.end_date
    //   )

    // GROUP BY 
    //     d.id, 
    //     pi.id

    // ORDER BY d.id DESC

    //         ";
    //         // dd($sql);
    //         // Return paginated result
    //         return DB::table(DB::raw("($sql) as subquery"))
    //             ->paginate($perPage);
    //     }

    public function filterInvoiceDetails(array $filters, int $perPage = 50)
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

        WHERE cid.promotion_id != 0
        $where
        AND cih.invoice_date >= '$from'
        AND cih.invoice_date <= '$to'
        $notExistsCondition

        GROUP BY d.id
        ORDER BY d.id DESC
    ";
// dd($sql);
        $data = DB::table(DB::raw("($sql) as subquery"))->paginate($perPage);

        return [
            "compiled_exists" => $compiledExists,
            "data" => $data
        ];
    }
}