<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReturnHCollapseExport implements
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->warehouseIds = $warehouseIds;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 1;

        // 🔹 MAIN HEADER
        $rows[] = [
            'OSA Code','Customer','Company','Warehouse',
            'Driver','Turnman','Truck No','Vat','Net','Total','Contact'
        ];
        $rowIndex++;

        // 🔥 HEADERS (LIGHT + FILTERED)
        $headers = HtReturnHeader::with([
                'customer:id,osa_code,business_name',
                'company:id,company_code,company_name',
                'warehouse:id,warehouse_code,warehouse_name',
                'driver:id,osa_code,driver_name'
            ])
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereDate('created_at', '>=', $this->fromDate)
                  ->whereDate('created_at', '<=', $this->toDate);
            })
            ->when($this->warehouseIds, fn($q)=>$q->whereIn('warehouse_id',$this->warehouseIds))
            ->get();

        // 🔥 DETAILS BULK LOAD (FAST 🚀)
        $allDetails = HtReturnDetail::with([
                'item:id,erp_code,name',
                'uomdetails:id,name'
            ])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');

        foreach ($headers as $header) {

            $details = $allDetails[$header->id] ?? collect();

            // 🔹 HEADER ROW
            $rows[] = [
                $header->return_code,
                trim(($header->customer->osa_code ?? '').' - '.($header->customer->business_name ?? '')),
                trim(($header->company->company_code ?? '').' - '.($header->company->company_name ?? '')),
                trim(($header->warehouse->warehouse_code ?? '').' - '.($header->warehouse->warehouse_name ?? '')),
                trim(($header->driver->osa_code ?? '').' - '.($header->driver->driver_name ?? '')),
                $header->turnman,
                $header->truck_no,
                $header->vat ?: '',
                $header->net ?: '',
                $header->total ?: '',
                $header->contact_no,
            ];
            $rowIndex++;

            // 🔥 DETAIL HEADER (niche show hoga)
            $rows[] = [
                '',
                'Item','UOM Name','Qty','Expiry Date','Batch No','Return Type','Reason','Item Value','Net Detail','Detail VAT','Detail Total'
            ];
            $rowIndex++;

            $start = $rowIndex - 1;

            // 🔹 DETAILS
            foreach ($details as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '').' - '.($d->item->name ?? '')),
                    $d->uomdetails->name ?? '',
                    $d->qty,
                    $d->actual_expiry_date,
                    $d->batch_no,
                    $d->return_type,
                    $d->return_reason,
                    $d->item_value,
                    $d->net ?: '',
                    $d->vat ?: '',
                    $d->total ?: '',
                ];
                $rowIndex++;
            }

            // 🔹 GROUP
            if ($details->count()) {
                $this->groupIndexes[] = [
                    'start'=>$start,
                    'end'=>$rowIndex-1
                ];
            }

            $rows[] = [''];
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // 🔹 HEADER STYLE
                $sheet->getStyle("A1:K1")->applyFromArray([
                    'fill'=>[
                        'fillType'=>Fill::FILL_SOLID,
                        'startColor'=>['rgb'=>'993442']
                    ],
                    'font'=>[
                        'bold'=>true,
                        'color'=>['rgb'=>'FFFFFF']
                    ],
                ]);

                // 🔥 DETAIL HEADER STYLE
                for ($i=2;$i<=$lastRow;$i++) {
                    if ($sheet->getCell("B{$i}")->getValue()==='Item') {
                        $sheet->getStyle("B{$i}:L{$i}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$i}:L{$i}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                // 🔹 COLLAPSE
                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false)
                            ->setCollapsed(true);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}