<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Warehouse;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;


class HtReturnService
{
    public function list(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = HtReturnHeader::with([
            'customer',
            'company',
            'warehouse',
            'driver'
        ]);
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'ILIKE', "%{$search}%")
                    ->orWhere('return_code', 'ILIKE', "%{$search}%")
                    ->orWhere('truck_no', 'ILIKE', "%{$search}%")
                    ->orWhere('contact_no', 'ILIKE', "%{$search}%")
                    ->orWhere('sap_id', 'ILIKE', "%{$search}%")
                    ->orWhere('message', 'ILIKE', "%{$search}%")
                    ->orWhere('turnman', 'ILIKE', "%{$search}%");
                $q->orWhereHas('customer', function ($qc) use ($search) {
                    $qc->where('osa_code', 'ILIKE', "%{$search}%")
                        ->orWhere('business_name', 'ILIKE', "%{$search}%")
                        ->orWhere('town', 'ILIKE', "%{$search}%")
                        ->orWhere('contact_no', 'ILIKE', "%{$search}%");
                });
                $q->orWhereHas('company', function ($qc) use ($search) {
                    $qc->where('company_code', 'ILIKE', "%{$search}%")
                        ->orWhere('company_name', 'ILIKE', "%{$search}%");
                });
                $q->orWhereHas('warehouse', function ($qw) use ($search) {
                    $qw->where('warehouse_code', 'ILIKE', "%{$search}%")
                        ->orWhere('warehouse_name', 'ILIKE', "%{$search}%");
                });
                $q->orWhereHas('driver', function ($qd) use ($search) {
                    $qd->where('driver_name', 'ILIKE', "%{$search}%")
                        ->orWhere('osa_code', 'ILIKE', "%{$search}%")
                        ->orWhere('contactno', 'ILIKE', "%{$search}%");
                });
            });
        }
        $exactFilters = [
            'customer_id',
            'company_id',
            'warehouse_id',
            'driver_id',
            'sap_id',
            'truck_no',
            'status'
        ];
        foreach ($exactFilters as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        $fromDate = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : null;

        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : null;

        if ($fromDate || $toDate) {

            if ($fromDate && $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);
            } elseif ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            } elseif ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        } else {
            $query->whereDate('created_at', Carbon::today());
        }
        $allowedSorts = [
            'created_at',
            'return_code',
            'sap_id',
            'status',
            'total'
        ];
        $sortBy    = in_array($filters['sort_by'] ?? '', $allowedSorts)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc'
            ? 'asc'
            : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        if ($dropdown) {
            return $query->select('id', 'return_code')
                ->orderBy('return_code')
                ->get()
                ->map(fn($item) => [
                    'id'    => $item->id,
                    'label' => $item->return_code,
                    'value' => $item->id,
                ]);
        }
        return $query->paginate($perPage);
    }

    public function viewByUuid(string $uuid)
    {
        return HtReturnHeader::with(['details.item', 'details.uomdetails', 'customer', 'warehouse', 'company'])
            ->where('uuid', $uuid)
            ->first();
    }

    // public function fetchBatch($expiryDate, $itemId, $customerId, $qty, $uomId)
    // {
    //     $convertedDate = Carbon::parse($expiryDate)->format('Y-m-d');

    //     $newDate = date("Y-m-d", strtotime($convertedDate . " +5 days"));
    //     $oldDate = date("Y-m-d", strtotime($convertedDate . " -5 days"));

    //     if ($uomId == 1 || $uomId == 3) {

    //         $upc = DB::table('item_uoms')
    //             ->where('item_id', $itemId)
    //             ->where('uom_id', $uomId) 
    //             ->value('upc');

    //         if ($upc && $upc > 0) {
    //             $qty = round($qty / $upc, 2);
    //         }
    //     }
    //     return DB::table('ht_invoice_detail as d')
    //         ->leftJoin('ht_invoice_header as h', 'h.id', '=', 'd.header_id')
    //         ->leftJoin('items as i', 'i.id', '=', 'd.item_id')
    //         ->leftJoin('item_uoms as iu', function ($join) {
    //             $join->on('iu.item_id', '=', 'd.item_id');
    //         })
    //         ->select(
    //             'd.batch_number',
    //             'h.sap_id',
    //             'd.item_price',
    //             'iu.upc as uom_upc',  
    //             'd.inv_position_no'
    //         )
    //         ->whereBetween('d.batch_expiry_date', [$oldDate, $newDate])
    //         ->where('d.item_id', $itemId)
    //         ->where('d.quantity', '>=', $qty)
    //         ->where('d.item_price', '!=', '0.00')
    //         ->where('h.customer_id', $customerId)
    //         ->first();
    // }


    public function processReturn($post)
    {
        $categoryArray     = explode(",", $post['category_id_data']);
        $batchArray        = explode(",", $post['batch_number_Array']);
        $expiryArray       = explode(",", $post['expery_date_Array']);
        $actualExpiryArray = explode(",", $post['actual_date_Array']);
        $remarkArray       = explode(",", $post['itemremark_Array']);
        $sapArray          = explode(",", $post['sap_id_Array']);
        $posnrArray        = explode(",", $post['Posnr_Array']);
        $priceArray        = explode(",", $post['priceArray']);
        $qtyArray          = explode(",", $post['quantitydata']);
        $itemArray         = explode(",", $post['itemiddata']);
        $totalArray        = explode(",", $post['totaldata']);
        $uomArray          = explode(",", $post['umodaynamic']);
        $reasonArray       = explode(",", $post['reason']);
        $productType       = explode(",", $post['return_type']);
        $customer_id = $post['customer_id'];
        $login_user  = auth()->user();
        $returnData = get_return_code();
        $headerInsert = [
            'invoice_code'  => $returnData['return_code'],
            'invoice_number' => $returnData['return_number'],
            'customer_id'   => $customer_id,
            'invoice_date'  => $post['valid_from'],
            'total'         => $post['totalcnt'],
            'net'           => $post['totalcnt'],
            'vat'           => 0,
            'salesman_id'   => $login_user->id,
            'currency_id'   => $post['pricelistId'],
        ];
        $headerId = DB::table('ht_invoice_header')->insertGetId($headerInsert);
        if ($post['rootid'] != 0) {
            $warehouse = DB::table('tbl_warehouse')
                ->where('id', function ($q) use ($post) {
                    $q->select('depot_id')
                        ->from('tbl_rms')
                        ->where('id', $post['rootid']);
                })
                ->first();
        } else {
            $warehouse = DB::table('tbl_warehouse')
                ->where('company_customer_id', $customer_id)
                ->first();
        }
        foreach ($itemArray as $key => $item_id) {
            $invoiceCheck = DB::table('customerinvoicedetail as cd')
                ->leftJoin('customerinvoiceheader as ch', 'ch.id', '=', 'cd.headerid')
                ->where('ch.sap_id', $sapArray[$key])
                ->where('cd.batch_number', $batchArray[$key])
                ->where('cd.item_id', $item_id)
                ->first();
            if (!$invoiceCheck) {
                continue;
            }
            $is_free = 0;
            $parent = 0;
            if (preg_match("#[^0-9]#", $item_id)) {
                $tmp = explode('_', $item_id);
                if (!empty($tmp)) {
                    $is_free = 1;
                    $parent = $tmp[1];
                }
            }
            $stock = DB::table('tbl_warehouse')
                ->where('id', $warehouse->id)
                ->first();
            DB::table('ht_invoice_detail')->insert([
                'header_id'         => $headerId,
                'item_id'           => $item_id,
                'batch_number'      => $batchArray[$key],
                'batch_expiry_date' => Carbon::parse($expiryArray[$key])->format('Y-m-d'),
                'item_sap_id'       => $sapArray[$key],
                'inv_position_no'   => $posnrArray[$key],
                'quantity'          => $qtyArray[$key],
                'item_price'        => $priceArray[$key],
                'total'             => $totalArray[$key],
                'uom_id'            => $uomArray[$key],
                'comment_for_rejection' => $remarkArray[$key],
                'status'            => 1,
            ]);
        }
        if ($post['invoice_agent'] == 1) {
            $groups = DB::table('ht_invoice_detail')
                ->select('item_sap_id', DB::raw('SUM(total) as total_amt'))
                ->where('header_id', $headerId)
                ->groupBy('item_sap_id')
                ->get();
            $i = 1;
            foreach ($groups as $grp) {
                $tempHeaderId = DB::table('temp_return_header')->insertGetId([
                    'return_code' => $returnData['return_code'],
                    'customer_id' => $customer_id,
                    'amount'      => $grp->total_amt,
                    'sap_id'      => $grp->item_sap_id,
                    'parent_id'   => $headerId
                ]);
                $details = DB::table('ht_invoice_detail')
                    ->where('header_id', $headerId)
                    ->where('item_sap_id', $grp->item_sap_id)
                    ->get();
                foreach ($details as $d) {
                    DB::table('temp_return_details')->insert([
                        'header_id'         => $tempHeaderId,
                        'item_id'           => $d->item_id,
                        'batchno'           => $d->batch_number,
                        'expiry_batch'      => $d->batch_expiry_date,
                        'uom'               => $d->uom_id,
                        'qty'               => $d->quantity,
                        'total'             => $d->total,
                        'actual_expiry_date' => $d->batch_manuf_date,
                        'remark'            => $d->comment_for_rejection,
                        'invoice_sap_id'    => $d->item_sap_id
                    ]);
                }
                $i++;
            }
        }
        return [
            'header_id' => $headerId,
            'message'   => 'Return created'
        ];
    }


    //  public function createReturn(array $data)
    //     {
    //         $loginUser = auth()->user();
    //         $returnData = function_exists('get_return_code') ? get_return_code() : ['return_code' => 'RTN-'.time(), 'return_number' => time()];

    //         $headerInsert = [
    //             'uuid' => (string) Str::uuid(),
    //             'osa_code' => $returnData['return_code'],
    //             'currency' => $data['pricelistId'] ?? null,
    //             'customer_id' => $data['customer_id'],
    //             'invoice_date' => $data['valid_from'],
    //             'gross_total' => $data['totalcnt'],
    //             'net_amount' => $data['totalcnt'],
    //             'total' => $data['totalcnt'],
    //             'vat' => 0,
    //             'salesman_id' => $loginUser->id ?? null,
    //             'status' => 1,
    //             'created_user' => $loginUser->id ?? null,
    //             'created_at' => Carbon::now()
    //         ];
    //         $headerId = DB::table('ht_invoice_header')->insertGetId($headerInsert);

    //         $warehouse = null;
    //         if (!empty($data['rootid']) && $data['rootid'] != 0) {
    //             $warehouse = DB::table('tbl_warehouse')
    //                 ->where('id', function ($q) use ($data) {
    //                     $q->select('depot_id')
    //                         ->from('tbl_rms')
    //                         ->where('id', $data['rootid']);
    //                 })
    //                 ->first();
    //         } else {
    //             $warehouse = DB::table('tbl_warehouse')
    //                 ->where('company_customer_id', $data['customer_id'])
    //                 ->first();
    //         }

    //         foreach ($data['items'] as $item) {
    //             $invoiceCheck = DB::table('customerinvoicedetail as cd')
    //                 ->leftJoin('customerinvoiceheader as ch', 'ch.id', '=', 'cd.headerid')
    //                 ->where('ch.sap_id', $item['sap'])
    //                 ->where('cd.batch_number', $item['batch'])
    //                 ->where('cd.item_id', $item['item'])
    //                 ->first();

    //             if (!$invoiceCheck) {
    //                 continue;
    //             }

    //             DB::table('ht_invoice_detail')->insert([
    //                 'uuid' => (string) Str::uuid(),
    //                 'header_id' => $headerId,
    //                 'item_id' => $item['item'],
    //                 'batch_number' => $item['batch'],
    //                 'batch_expiry_date' => $item['expiry'] ? Carbon::parse($item['expiry'])->format('Y-m-d') : null,
    //                 'batch_manuf_date' => $item['actual_expiry'] ? Carbon::parse($item['actual_expiry'])->format('Y-m-d') : null,
    //                 'item_sap_id' => $item['sap'],
    //                 'inv_position_no' => $item['posnr'],
    //                 'quantity' => $this->toNumeric($item['qty']),
    //                 'item_price' => $this->toNumeric($item['price']),
    //                 'total' => $this->toNumeric($item['total']),
    //                 'uom_id' => $item['uom'],
    //                 'comment_for_rejection' => $item['remark'],
    //                 'status' => 1,
    //                 'created_user' => $loginUser->id ?? null,
    //                 'created_at' => Carbon::now()
    //             ]);
    //         }

    //         if (!empty($data['invoice_agent']) && $data['invoice_agent'] == 1) {
    //             $details = DB::table('ht_invoice_detail')->where('header_id', $headerId)->get()->toArray();
    //             $collection = collect($details);

    //             $grouped = $collection->groupBy(function ($d) {
    //                 return ($d->comment_for_rejection ?: '0') . '||' . ($d->item_sap_id ?: 'NOSAP') . '||' . ($d->batch_number ?: 'NOBATCH') . '||' . ($d->status ?: '0') . '||' . ($d->batch_expiry_date ?: 'NODATE') . '||' . ($d->item_id ?: '0') . '||' . ($d->quantity ?: '0') . '||' . ($d->total ?: '0') . '||' . ($d->item_sap_id ?: 'NOSAP') . '||' . ($d->batch_number ?: 'NOBATCH') . '||' . ($d->item_id ?: '0') . '||' . ($d->batch_number ?: 'NOBATCH');
    //             });

    //             $groupsByTypeAndBatch = [];
    //             foreach ($collection as $d) {
    //                 $rtype = DB::table('ht_invoice_detail')->where('id', $d->id)->value('return_type') ?? null;
    //                 $key = ($rtype ?? '0') . '||' . ($d->batch_number ?? 'NOBATCH');
    //                 if (!isset($groupsByTypeAndBatch[$key])) {
    //                     $groupsByTypeAndBatch[$key] = [];
    //                 }
    //                 $groupsByTypeAndBatch[$key][] = (array) $d;
    //             }

    //             foreach ($groupsByTypeAndBatch as $groupKey => $rows) {
    //                 $first = $rows[0];
    //                 $sumAmount = array_sum(array_map(function ($r) { return (float) ($r['total'] ?? 0); }, $rows));
    //                 $sapId = $first['item_sap_id'] ?? null;
    //                 $rtype = null;
    //                 foreach ($rows as $r) {
    //                     $rtype = ($r['return_type'] ?? $rtype) ?: $rtype;
    //                 }
    //                 $tempHeaderId = DB::table('temp_return_header')->insertGetId([
    //                     'uuid' => (string) Str::uuid(),
    //                     'return_code' => $returnData['return_code'],
    //                     'customer_id' => $data['customer_id'],
    //                     'amount' => $sumAmount,
    //                     'sap_id' => $sapId,
    //                     'parent_id' => $headerId,
    //                     'return_type' => $rtype,
    //                     'created_user' => $loginUser->id ?? null,
    //                     'created_at' => Carbon::now()
    //                 ]);

    //                 foreach ($rows as $r) {
    //                     DB::table('temp_return_details')->insert([
    //                         'uuid' => (string) Str::uuid(),
    //                         'header_id' => $tempHeaderId,
    //                         'posnr' => $r['inv_position_no'] ?? null,
    //                         'item_id' => $r['item_id'] ?? null,
    //                         'item_value' => $r['item_price'] ?? 0,
    //                         'vat' => $r['vat'] ?? 0,
    //                         'uom' => $r['uom_id'] ?? null,
    //                         'qty' => $r['quantity'] ?? 0,
    //                         'net' => $r['total'] ?? 0,
    //                         'total' => $r['total'] ?? 0,
    //                         'expiry__batch' => $r['batch_expiry_date'] ?? null,
    //                         'batchno' => $r['batch_number'] ?? null,
    //                         'actual_expiry_date' => $r['batch_manuf_date'] ?? null,
    //                         'remark' => $r['comment_for_rejection'] ?? null,
    //                         'invoice_sap_id' => $r['item_sap_id'] ?? null,
    //                         'created_user' => $loginUser->id ?? null,
    //                         'created_at' => Carbon::now()
    //                     ]);
    //                 }
    //             }
    //         }

    //         return [
    //             'header_id' => $headerId,
    //             'message' => 'Return created'
    //         ];
    //     }

    //     private function toNumeric($val)
    //     {
    //         if ($val === null || $val === '') {
    //             return 0;
    //         }
    //         return is_numeric($val) ? $val + 0 : floatval(str_replace(',', '', $val));
    //     }

    // public function create(array $data): HtReturnHeader
    // {
    //     return DB::transaction(function () use ($data) {

    //         $returnCode = $this->generateReturnCode($data['return_code'] ?? null);

    //         $header = HtReturnHeader::create([
    //             'uuid'          => Str::uuid(),
    //             'return_code'   => $returnCode,
    //             'customer_id'   => $data['customer_id'],
    //             'company_id'    => $data['company_id']  ?? null,
    //             'warehouse_id'  => $data['warehouse_id'] ?? null,
    //             'driver_id'     => $data['driver_id'] ?? null,
    //             'net'           => $data['net'],
    //             'vat'           => $data['vat'],
    //             'turnman'       => $data['turnman'] ?? null,
    //             'truck_no'      => $data['truck_no'] ?? null,
    //             'contact_no'    => $data['contact_no'] ?? null,
    //             'total'         => $data['total'],
    //             'message'       => $data['comment'] ?? null,
    //             'status'        => $data['status'],
    //             'sap_id'        => $data['sap_id'] ?? null,
    //         ]);

    //         foreach ($data['details'] as $row) {
    //             HtReturnDetail::create([
    //                 'header_id'           => $header->id,
    //                 'item_id'             => $row['item_id'],
    //                 'item_value'          => $row['item_price'],
    //                 'qty'                 => $row['quantity'],
    //                 'vat'                 => $row['vat'] ?? 0,
    //                 'uom'                 => $row['uom_id'],
    //                 'net'                 => $row['net_total'] ?? 0,
    //                 'total'               => $row['total'],
    //                 'batch_no'            => $row['batch_number'] ?? null,
    //                 'actual_expiry_date'  => $row['expiry_date'] ?? null,
    //                 'return_type'         => $row['type'],
    //                 'return_reason'       => $row['reason'] ?? null,
    //                 'posnr'               => $row['posnr'] ?? null,
    //                 'invoice_sap_id'      => $row['invoice_sap_id'] ?? null,
    //                 'return_date'         => $row['return_date'] ?? null,
    //             ]);
    //         }

    //         if (!empty($data['invoice_agent']) && $data['invoice_agent'] == 0) {

    //             $details = HtReturnDetail::where('header_id', $header->id)->get();
    //             $grouped = $details->groupBy(function ($row) {
    //                 return $row->invoice_sap_id . '||' . ($row->return_reason ?? 'NOREASON');
    //             });

    //             foreach ($grouped as $groupRows) {

    //                 $firstRow = $groupRows->first();
    //                 if (empty($firstRow->invoice_sap_id)) {
    //                     continue;
    //                 }

    //                 $total = $groupRows->sum('total');
    //                 $vat   = $groupRows->sum('vat');
    //                 $net   = $groupRows->sum('net');
    //                 $tempHeaderId = DB::table('temp_return_header')->insertGetId([
    //                     'uuid'                        => (string) Str::uuid(),
    //                     'return_code'                 => $header->return_code,
    //                     'customer_id'                 => $header->customer_id,
    //                     'invoice_sap_id'              => $firstRow->invoice_sap_id,
    //                     'customer_returnheader_sapid' => $header->sap_id,
    //                     'return_reason'               => $firstRow->return_reason,
    //                     'return_type'                 => $firstRow->return_type,
    //                     'vat'                         => $vat,
    //                     'net'                         => $net,
    //                     'total'                       => $total,
    //                     'parent_header_id'            => $header->id,
    //                     'created_at'                  => now(),
    //                 ]);
    //                 foreach ($groupRows as $row) {
    //                     DB::table('temp_return_details')->insert([
    //                         'uuid'               => (string) Str::uuid(),
    //                         'header_id'          => $tempHeaderId,
    //                         'posnr'              => $row->posnr,
    //                         'item_id'            => $row->item_id,
    //                         'item_value'         => $row->item_value,
    //                         'vat'                => $row->vat,
    //                         'uom'                => $row->uom,
    //                         'qty'                => $row->qty,
    //                         'net'                => $row->net,
    //                         'total'              => $row->total,
    //                         'batch_no'           => $row->batch_no,
    //                         'actual_expiry_date' => $row->actual_expiry_date,
    //                         'remark'             => $row->return_reason,
    //                         'created_at'         => now(),
    //                     ]);
    //                 }
    //             }
    //         }

    //         return $header->load('details');
    //     });
    // }


    public function storeData(array $data): HtReturnHeader
    {
        return DB::transaction(function () use ($data) {

            $warehouse = Warehouse::select('id', 'company_customer_id')
                ->where('id', $data['warehouse_id'])
                ->firstOrFail();
            $customerId = $warehouse->company_customer_id;
            $warehouse = Warehouse::select('id', 'company')
                ->where('id', $data['warehouse_id'])
                ->firstOrFail();
            $companyId = $warehouse->company;

            $returnCode = $this->generateReturnCode($data['return_code'] ?? null);

            $header = HtReturnHeader::create([
                'uuid'         => (string) Str::uuid(),
                'return_code'  => $returnCode,
                'customer_id'  => $customerId,
                'company_id'   => $companyId,
                'warehouse_id' => $data['warehouse_id'],
                'driver_id'    => $data['driver_id'] ?? null,
                'net'          => $data['net'],
                'vat'          => $data['vat'],
                'turnman'      => $data['turnman'] ?? null,
                'truck_no'     => $data['truck_no'] ?? null,
                'contact_no'   => $data['contact_no'] ?? null,
                'total'        => $data['total'],
                'message'      => $data['comment'] ?? null,
                'status'       => $data['status'],
                'sap_id'       => $data['sap_id'] ?? null,
                'created_user' => auth()->id(),
            ]);


            foreach ($data['details'] as $row) {
                HtReturnDetail::create([
                    'uuid'               => (string) Str::uuid(),
                    'header_id'          => $header->id,
                    'item_id'            => $row['item_id'],
                    'item_value'         => $row['item_price'],
                    'qty'                => $row['quantity'],
                    'vat'                => $row['vat'] ?? 0,
                    'uom'                => $row['uom_id'],
                    'net'                => $row['net_total'] ?? 0,
                    'total'              => $row['total'],
                    'batch_no'           => $row['batch_number'] ?? null,
                    'actual_expiry_date' => $row['expiry_date'] ?? null,
                    'return_type'        => $row['type'],
                    'return_reason'      => $row['reason'] ?? null,
                    'posnr'              => $row['posnr'] ?? null,
                    'invoice_sap_id'     => $row['invoice_sap_id'] ?? null,
                    'return_date'        => $row['return_date'] ?? null,
                    'created_user'       => auth()->id(),
                ]);
            }


            $details = HtReturnDetail::where('header_id', $header->id)->get();
            $grouped = $details->groupBy(function ($row) use ($header) {
                return implode('||', [
                    (string) $row->invoice_sap_id,
                    (string) ($row->return_reason ?? 'NOREASON'),
                    (string) $header->customer_id,
                ]);
            });

            foreach ($grouped as $groupRows) {

                $firstRow = $groupRows->first();
                if (empty($firstRow->invoice_sap_id)) {
                    continue;
                }

                $total = $groupRows->sum('total');
                $vat   = $groupRows->sum('vat');
                $net   = $groupRows->sum('net');

                $tempCount = DB::table('temp_return_header')
                    ->where('parent_header_id', $header->id)
                    ->count();

                $tempReturnCode = $header->return_code . '_' . str_pad($tempCount + 1, 2, '0', STR_PAD_LEFT);
                $tempHeaderId = DB::table('temp_return_header')->insertGetId([
                    'uuid'                        => (string) Str::uuid(),
                    'return_code'                 => $tempReturnCode,
                    'customer_id'                 => $header->customer_id,
                    'invoice_sap_id'              => (string) $firstRow->invoice_sap_id,
                    'customer_returnheader_sapid' => (string) $header->sap_id,
                    'return_reason'               => $firstRow->return_reason,
                    'return_type'                 => $firstRow->return_type,
                    'vat'                         => $vat,
                    'net'                         => $net,
                    'total'                       => $total,
                    'sap_return_msg'              => 'PENDING' ?? null,
                    'parent_header_id'            => $header->id,
                    'created_user'                => auth()->id(),
                    'created_at'                  => now(),
                ]);

                foreach ($groupRows as $row) {
                    DB::table('temp_return_details')->insert([
                        'uuid'               => (string) Str::uuid(),
                        'header_id'          => $tempHeaderId,
                        'posnr'              => $row->posnr ?? null,
                        'item_id'            => $row->item_id,
                        'item_value'         => $row->item_value,
                        'vat'                => $row->vat,
                        'uom'                => $row->uom,
                        'qty'                => $row->qty,
                        'net'                => $row->net,
                        'total'              => $row->total,
                        'batch_no'           => $row->batch_no,
                        'actual_expiry_date' => $row->actual_expiry_date,
                        'remark'             => $row->return_reason,
                        'created_user'       => auth()->id(),
                        'created_at'         => now(),
                    ]);
                }
            }
            return $header->load('details');
        });
    }


    private function generateReturnCode(?string $payloadCode = null): string
    {
        if (!empty($payloadCode)) {
            return $payloadCode;
        }
        $lastNumber = DB::table('ht_return_header')
            ->whereNotNull('return_code')
            ->where('return_code', 'LIKE', 'HTRTN-%')
            ->whereRaw("SUBSTRING(return_code FROM 7) ~ '^[0-9]+$'")
            ->select(DB::raw("MAX(CAST(SUBSTRING(return_code FROM 7) AS INTEGER)) AS max_no"))
            ->value('max_no');

        $nextNumber = ($lastNumber ?? 0) + 1;

        return 'HTRTN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    // private function generateReturnNo(): string
    // {
    //     do {
    //         $number = random_int(1000, 9999);
    //         $returnNo = 'RTN-' . $number;
    //     } while (
    //         DB::table('ht_return_header')
    //             ->where('return_no', $returnNo)
    //             ->exists()
    //     );

    //     return $returnNo;
    // }


    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $user = auth()->user();

        $filter = $filters['filter'] ?? [];

        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }

        $query = HtReturnHeader::latest('id');

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

        // Status filter
        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        // Delivery date range
        if (!empty($filter['from_date'])) {
            $query->whereDate('created_at', '>=', $filter['from_date']);
        }

        if (!empty($filter['to_date'])) {
            $query->whereDate('created_at', '<=', $filter['to_date']);
        }
        // dd($query->count());
        return $query->paginate($perPage);
    }

     public function getByWarehouseId($warehouseId, $perPage = 10)
    {
        return HtReturnHeader::where('warehouse_id', $warehouseId)
            ->paginate($perPage);
    }
}
 