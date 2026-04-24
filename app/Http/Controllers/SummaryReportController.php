<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;

class SummaryReportController extends Controller
{
   
    public function export(Request $request)
      {
            $format = strtolower($request->input('format', 'xlsx'));
            $extension = $format === 'csv' ? 'csv' : 'xlsx';

            $filename = 'SummaryReport_' . now()->format('Ymd_His') . '.' . 'csv';
            $path = 'capscollectionexports/' . $filename;

            $depotIds = $request->manager_agent_id;
            $year = $request->year;
            $months = $request->monthRange;

            // 🔹 Claim Details
            $claimDetails = [
                'ZBAC' => 'Agent Bonus - BEV',
                'ZCAC' => 'Agent Target Inc-BEV',
                'ZFAC' => 'Agent Fuel /Offloading Comp-BEV',
                'ZPAC' => 'Trade Promotion- BEV',
                'ZDAC' => 'Trade Price Vari-BEV',
                'ZSAC' => 'Sampling /Trd support',
                'ZKAC' => 'Consumer Promotion - CAPS',
                'ZCOM' => 'Commission Compensation'
            ];

            // 🔥 Fetch Data
            $depots = $this->getDepots($depotIds);
            $promo = $this->getMarketPromo($year, $months, $depotIds);
            $reb = $this->getReimbursement($year, $months, $depotIds);
            $collection = $this->getCollection($year, $months, $depotIds);
            $caps = $this->getCaps($year, $months, $depotIds);
            $trade = $this->getTradeVari($year, $months, $depotIds);
            $opening = $this->getOpening($depotIds);

            // 🔥 Summary Array
            $summary = [];

            foreach ($promo as $p) {
                preg_match('/[a-zA-Z]+ \d{4}/', $p->month_range, $matches);
                $month = date('m', strtotime("1 " . $matches[0]));

                $summary['promotion'][$month][$p->warehouse_id] =
                    ($summary['promotion'][$month][$p->warehouse_id] ?? 0) + $p->total;

                $summary['totalQty'][$month][$p->warehouse_id] =
                    ($summary['totalQty'][$month][$p->warehouse_id] ?? 0) + $p->totalQty;
            }

            foreach ($reb as $r) {
                $summary['rebursment'][$r->item_categoryDLV][$r->month1][$r->warehouse_id] =
                    ($summary['rebursment'][$r->item_categoryDLV][$r->month1][$r->warehouse_id] ?? 0) + $r->total;
            }

            foreach ($collection as $c) {
                if ($c->claim_type == 2) {
                    $summary['collection'][$c->month_range][$c->warehouse_id] =
                        ($summary['collection'][$c->month_range][$c->warehouse_id] ?? 0) + $c->agent_amount;
                } else {
                    $summary['fuel'][$c->month_range][$c->warehouse_id] =
                        ($summary['fuel'][$c->month_range][$c->warehouse_id] ?? 0) + ($c->fuel_amount + $c->rent_amount);
                }
            }

            foreach ($caps as $c) {
                $summary['capsamount'][$c->month1][$c->warehouse_id] =
                    ($summary['capsamount'][$c->month1][$c->warehouse_id] ?? 0) + $c->approved_amount;

                $summary['capsqty'][$c->month1][$c->warehouse_id] =
                    ($summary['capsqty'][$c->month1][$c->warehouse_id] ?? 0) + $c->approved_qty;
            }

            foreach ($trade as $t) {
                $summary['tradevari'][$t->month1][$t->warehouse_id] =
                    ($summary['tradevari'][$t->month1][$t->warehouse_id] ?? 0) + ($t->total * 30);
            }

            foreach ($opening as $o) {
                $summary['opening'][$o->claim_code][$o->warehouse_id] =
                    ($summary['opening'][$o->claim_code][$o->warehouse_id] ?? 0) + $o->amount;
               $summary['openingmonth'][$o->claim_code][$o->month1][$o->warehouse_id] =($summary['openingmonth'][$o->claim_code][$o->month1][$o->warehouse_id] ?? 0) + $o->amount;
            }

            // ✅ FILE CREATE
            $fullPath = storage_path('app/public/' . $path);
            $handle = fopen($fullPath, 'w');

            // 🔹 Heading
            $heading = ['Code','Agent Name','ASM Name','RSM','Type','Claim Code','Opening Balance'];

            foreach ($months as $month) {
                $date = "$year-$month-01";
                $heading[] = date('F', strtotime($date)) . ' Qty Case';
                $heading[] = date('F', strtotime($date)) . ' Qty PC';
                $heading[] = date('F', strtotime($date)) . ' Claimed';
                $heading[] = date('F', strtotime($date)) . ' Reimbursed';
                $heading[] = date('F', strtotime($date)) . ' Balance';
            }

            $heading[] = 'Total Claimed';
            $heading[] = 'Total Reimbursed';
            $heading[] = 'Pending Balance';

            fputcsv($handle, $heading);

            // 🔹 Data
            foreach ($depots as $row) {
                foreach ($claimDetails as $key => $value) {

                    $line = [
                        $row->warehouse_code,
                        $row->warehouse_name,
                        $row->name . ' ' . $row->username,
                        $row->first . ' ' . $row->last,
                        $value,
                        $key,
                        number_format($summary['opening'][$key][$row->id] ?? 0, 2)
                    ];

                    $totalClaim = 0;
                    $totalReim = 0;
                    $totalBal = 0;

                    // foreach ($months as $month) {

                    //     $claim = 0;
                    //     $Qtypcs = 0;
                    //     $Qtycse = 0;
                    //     $balance = 0;
                    //     $openingmonth = $summary['openingmonth'][$key][$month][$row->id] ?? 0;

                    //     if ($key == 'ZPAC') $claim = $summary['promotion'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZPAC') $Qtycse = $summary['totalQty'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZKAC') $claim = $summary['capsamount'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZKAC') $Qtycse = $summary['capsqty'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZFAC') $claim = $summary['fuel'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZCOM') $claim = $summary['collection'][$month][$row->id] ?? 0;
                    //     if ($key == 'ZDAC') $claim = $summary['tradevari'][$month][$row->id] ?? 0;

                    //     $reim = $summary['rebursment'][$key][$month][$row->id] ?? 0;
                    //     $balance = $openingmonth + $claim - $reim;
                    //     $claim = $claim + $openingmonth;

                    //     $line[] = number_format($Qtycse, 2);
                    //     $line[] = number_format($Qtypcs, 2);
                    //     $line[] = number_format($claim, 2);
                    //     $line[] = number_format($reim, 2);
                    //     $line[] = number_format($balance, 2);

                    //     $totalClaim += $claim;
                    //     $totalReim += $reim;
                    //     $totalBal += $balance;
                    // }

                    $mapping = [
                            'ZPAC' => ['claim' => 'promotion', 'qty' => 'totalQty'],
                            'ZKAC' => ['claim' => 'capsamount', 'qty' => 'capsqty'],
                            'ZFAC' => ['claim' => 'fuel', 'qty' => null],
                            'ZCOM' => ['claim' => 'collection', 'qty' => null],
                            'ZDAC' => ['claim' => 'tradevari', 'qty' => null],
                        ];

                        foreach ($months as $month) {

                            $openingmonth = $summary['openingmonth'][$key][$month][$row->id] ?? 0;

                            $claim = 0;
                            $Qtycse = 0;
                            $Qtypcs = 0;

                            if (isset($mapping[$key])) {

                                $claimKey = $mapping[$key]['claim'];
                                $qtyKey   = $mapping[$key]['qty'];

                                // ✅ Claim
                                if ($claimKey) {
                                    $claim = $summary[$claimKey][$month][$row->id] ?? 0;
                                }

                                // ✅ Quantity
                                if ($qtyKey) {
                                    $Qtycse = $summary[$qtyKey][$month][$row->id] ?? 0;
                                }
                            }

                            $reim = $summary['rebursment'][$key][$month][$row->id] ?? 0;

                            // ✅ Calculations
                            $balance = $openingmonth + $claim - $reim;
                            $claim   = $claim + $openingmonth;

                            // ✅ Output
                            $line[] = number_format($Qtycse, 2);
                            $line[] = number_format($Qtypcs, 2);
                            $line[] = number_format($claim, 2);
                            $line[] = number_format($reim, 2);
                            $line[] = number_format($balance, 2);

                            // ✅ Totals
                            $totalClaim += $claim;
                            $totalReim  += $reim;
                            $totalBal   += $balance;
                        }

                    $line[] = number_format($totalClaim, 2);
                    $line[] = number_format($totalReim, 2);
                    $line[] = number_format($totalBal, 2);

                    fputcsv($handle, $line);
                }
            }

            fclose($handle);

            // 🔥 Download URL
            // $appUrl = rtrim(config('app.url'), '/');
            // $fullUrl = $appUrl . '/storage/' . $path;
            $appUrl = rtrim(config('app.url'), '/');
                $fullUrl = $appUrl . '/storage/app/public/' . $path;

            return response()->json([
                'status' => 'success',
                'download_url' => $fullUrl
            ]);
        }
   private function getDepots($ids)
    {
        return DB::table('tbl_warehouse as t2')
            ->leftJoin('users as k', function ($join) {
                $join->whereRaw("
                    t2.area_id IN (
                        SELECT value::int 
                        FROM jsonb_array_elements_text(k.area::jsonb)
                    )
                ")
                ->where('k.status', 1)
                ->where('k.role', 91);
                // ->where('k.is_list', 0);
            })
            ->leftJoin('users as kd', function ($join) {
                $join->whereRaw("
                    t2.region_id IN (
                        SELECT value::int 
                        FROM jsonb_array_elements_text(kd.region::jsonb)
                    )
                ")
                ->where('kd.role', 92)
                ->where('kd.status', 1);
                // ->where('kd.is_list', 0);
            })
            ->where('t2.status', 1)
            ->whereIn('t2.id', $ids)
            ->orderBy('t2.id', 'ASC')
            ->select(
                't2.id',
                't2.warehouse_code',
                't2.warehouse_name',
                'k.name',
                'k.username',
                'kd.name as first',
                'kd.username as last'
            )
            ->get();
    }

   private function getOpening($ids)
{
    return DB::table('tbl_agentopening_balance as ed')
        ->select(
            'ed.claim_code',
            DB::raw("CAST(ed.amount AS NUMERIC) as amount"),
            DB::raw("CAST(ed.warehouse_id AS INTEGER) as warehouse_id"),

            // ✅ FIX: DATE_FORMAT → EXTRACT
            DB::raw("LPAD(EXTRACT(MONTH FROM ed.opening_date)::text, 2, '0') as month1")
        )
        ->whereIn('ed.warehouse_id', $ids)
        ->get();
}

    private function getCollection($year, $months, $ids)
    {
        return DB::table('tbl_petit_claims as ed')
            ->select('ed.*', 'ed.warehouse_id')
            ->whereIn('ed.month_range', $months)
            ->where('ed.year', $year)
            ->whereIn('ed.warehouse_id', $ids)
            ->get();
    }

private function getCaps($year, $months, $ids)
{
    $months = array_map('intval', $months);
    $ids = array_map('intval', $ids);

    return DB::table('ht_caps_header as c')

        ->leftJoin('tbl_warehouse as dp', 'dp.id', '=', 'c.warehouse_id')

        ->leftJoin('ht_caps_details as d', 'd.header_id', '=', 'c.id')

        ->selectRaw("
            LPAD(EXTRACT(MONTH FROM c.claim_date)::text, 2, '0') as month1,

            SUM(
                CASE 
                    WHEN c.status != 0 
                    THEN d.receive_amount::NUMERIC
                    ELSE 0 
                END
            ) as approved_amount,

            SUM(
                CASE 
                    WHEN c.status = 0 
                    THEN c.claim_amount::NUMERIC
                    ELSE 0 
                END
            ) as pending_amount,

            SUM(
                CASE 
                    WHEN c.status != 0 
                    THEN d.receive_qty::NUMERIC
                    ELSE 0 
                END
            ) as approved_qty,

            dp.id as warehouse_id
        ")

        ->whereYear('c.claim_date', $year)

        ->whereIn(DB::raw('EXTRACT(MONTH FROM c.claim_date)'), $months)

        ->whereIn('dp.id', $ids)

        ->where('c.status', '!=', 2)

        ->groupByRaw("
            dp.id,
            EXTRACT(MONTH FROM c.claim_date)
        ")

        ->orderByRaw("EXTRACT(MONTH FROM c.claim_date)")

        ->get();
}
    private function getTradeVari($year, $months, $ids)
    {
        return DB::table('ht_delivery_detail as cd')
            ->join('ht_delivery_header as ch', 'ch.id', '=', 'cd.header_id')
            ->join('items as i', 'cd.item_id', '=', 'i.id')
            ->join('tbl_warehouse as dp', 'ch.customer_id', '=', 'dp.company_customer_id')

            ->select(
                DB::raw("CAST(dp.id AS INTEGER) as warehouse_id"),

                DB::raw("
                    SUM(
                        CASE 
                            WHEN cd.uom_id IN (2, 4) THEN cd.quantity
                            WHEN cd.uom_id IN (1, 3) THEN (cd.quantity / 12)
                            ELSE 0
                        END
                    ) as total
                "),

                // ✅ FIX: MONTH → EXTRACT
                DB::raw("LPAD(EXTRACT(MONTH FROM ch.delivery_date)::text, 2, '0') as month1")
            )

            // ✅ YEAR filter
            ->whereYear('ch.delivery_date', $year)

            // ✅ MONTH filter (correct way)
            ->whereIn(
                DB::raw("EXTRACT(MONTH FROM ch.delivery_date)"),
                array_map(fn($m) => (int)$m, $months)
            )

            ->whereIn('dp.id', $ids)

            // ✅ GROUP BY FIX
            ->groupBy(
                DB::raw("CAST(dp.id AS INTEGER)"),
                DB::raw("EXTRACT(MONTH FROM ch.delivery_date)")
            )

            ->orderBy(DB::raw("EXTRACT(MONTH FROM ch.delivery_date)"))

            ->get();
    }
    // private function getReimbursement($year, $months, $ids)
    // {
    //     return DB::table('customerinvoicedetail as t3')
    //         ->join('customerinvoiceheader as t1', 't1.id', '=', 't3.headerid')
    //         ->join('tbl_depot as t2', 't2.agent_id', '=', 't1.customerid')
    //         ->select(
    //             't3.item_categoryDLV',
    //             't2.id as depot_id',
    //             DB::raw("LPAD(MONTH(t1.invoice_date),2,'0') as month1"),
    //             DB::raw("SUM(t3.quantity * 1) as total")
    //         )
    //         ->whereYear('t1.invoice_date', $year)
    //         ->whereIn(DB::raw("LPAD(MONTH(t1.invoice_date),2,'0')"), $months)
    //         ->whereIn('t2.id', $ids)
    //         ->groupBy('t2.id', 't3.item_categoryDLV', DB::raw("MONTH(t1.invoice_date)"))
    //         ->get();
    // }

    private function getReimbursement($year, $months, $ids)
    {
        // dd($year, $months, $ids);
        return DB::table('ht_invoice_detail as t3')
            ->leftJoin('ht_invoice_header as t1', 't1.id', '=', 't3.header_id')
            ->leftJoin('tbl_warehouse as t2', 't2.id', '=', 't1.customer_id')
            ->leftJoin('tbl_company_customer as c', 'c.id', '=', 't1.customer_id')
            ->leftJoin('items as i', 'i.id', '=', 't3.item_id')

            // ✅ ASM (JSON FIX)
            ->leftJoin('users as k', function ($join) {
                $join->whereRaw("
                    t2.area_id IN (
                        SELECT value::int 
                        FROM jsonb_array_elements_text(k.area::jsonb)
                    )
                ")
                ->where('k.status', 1)
                ->where('k.role', 91);
                // ->where('k.is_list', 0);
            })

            // ✅ RSM (JSON FIX)
            ->leftJoin('users as kd', function ($join) {
                $join->whereRaw("
                    t2.region_id IN (
                        SELECT value::int 
                        FROM jsonb_array_elements_text(kd.region::jsonb)
                    )
                ")
                ->where('kd.role', 92)
                ->where('kd.status', 1);
                // ->where('kd.is_list', 0);
            })

            ->select(
                // 't3.id',
                't3.item_category_dll',
                DB::raw("CAST(t2.id AS INTEGER) as warehouse_id"),

                // ✅ FIX: MONTH → EXTRACT
                // DB::raw("LPAD(EXTRACT(MONTH FROM t1.invoice_date)::text, 2, '0') as month1"),
                DB::raw("EXTRACT(MONTH FROM t1.invoice_date) as month1"),

                DB::raw("
                    SUM(
                        CASE 
                            WHEN t3.uom_id IN (2, 4) THEN t3.quantity * i.alter_base_uom_vol
                            WHEN t3.uom_id IN (1, 3) THEN (t3.quantity / 12) * i.alter_base_uom_vol
                            ELSE 0
                        END
                    ) as total
                ")
            )

            ->where('t1.invoice_type', '!=', 3)

            // ✅ FIX: whereYear already uses EXTRACT internally (ok)
            ->whereYear('t1.invoice_date', $year)

            // ✅ FIX: MONTH filter
            // ->whereIn(
            //     DB::raw("LPAD(EXTRACT(MONTH FROM t1.invoice_date)::text, 2, '0')"),
            //     array_map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT), $months)
            // )
            ->whereIn(
                DB::raw("EXTRACT(MONTH FROM t1.invoice_date)"),
                array_map(fn($m) => (int)$m, $months)
            )

            ->whereIn('t2.id', $ids)

            ->whereIn('t3.item_category_dll', [
                'ZBAC','ZCAC','ZFAC','ZPAC','ZDAC','ZSAC','ZKAC','ZCOM'
            ])

            ->groupBy(
                DB::raw("CAST(t2.id AS INTEGER)"),
                't3.item_category_dll', 

                // ✅ FIX: GROUP BY month
                DB::raw("EXTRACT(MONTH FROM t1.invoice_date)")
            )
            ->orderBy(DB::raw("EXTRACT(MONTH FROM t1.invoice_date)"))
            ->get();
    }

 
   private function getMarketPromo($year, $months, $ids)
    {
        $range = [];

        foreach ($months as $month) {
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $monthName = date('F', strtotime("$year-$month-01"));

             $range[] = "{$year}-{$month}-01 to {$year}-{$month}-15";
             $range[] = "{$year}-{$month}-16 to {$year}-{$month}-31";
        }
        return DB::table('tbl_compiled_claim as ch')
            ->leftJoin('tbl_warehouse as d', function ($join) {
                $join->on(DB::raw('d.id'), '=', DB::raw('CAST(ch.warehouse_id AS INTEGER)'));
            })
            ->select(
                DB::raw("SUM(CAST(ch.approved_qty_cse AS NUMERIC)) as totalQty"),
                DB::raw("SUM(CAST(ch.approved_claim_amount AS NUMERIC)) as total"),
                DB::raw("CAST(ch.warehouse_id AS INTEGER) as warehouse_id"),
                'ch.claim_period'
            )
            ->whereIn('ch.claim_period', $range)
            ->whereIn(DB::raw('CAST(ch.warehouse_id AS INTEGER)'), $ids)
            ->groupBy(DB::raw('CAST(ch.warehouse_id AS INTEGER)'), 'ch.claim_period')
            ->get();
    }
}