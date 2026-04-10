<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HtInvoiceCollapseExport implements FromCollection, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct(
        $fromDate = null,
        $toDate = null,
        $warehouseIds = [],
        $salesmanIds = []
    ) {
        $this->fromDate = $fromDate ?: now()->toDateString();
        $this->toDate   = $toDate   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 1;

        // 🔹 MAIN HEADER
        $rows[] = [
            'Invoice Code','Invoice Date','Customer','Salesman',
            'Warehouse','VAT','Excise','Net','Total'
        ];
        $rowIndex++;

        // ✅ APPLY FILTERS (IMPORTANT)
        $headers = HTInvoiceHeader::with([
                'customer:id,osa_code,business_name',
                'salesman:id,osa_code,name',
                'warehouse:id,warehouse_code,warehouse_name'
            ])
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->when(!empty($this->warehouseIds), fn($q) => $q->whereIn('warehouse_id', $this->warehouseIds))
            ->when(!empty($this->salesmanIds), fn($q) => $q->whereIn('salesman_id', $this->salesmanIds))
            ->get();

        // 🔥 DETAILS BULK LOAD (FAST)
        $allDetails = HTInvoiceDetail::with([
                'item:id,erp_code,name',
                'uoms:id,name'
            ])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');

        foreach ($headers as $header) {

            $details = $allDetails[$header->id] ?? collect();

            // 🔹 HEADER ROW
            $rows[] = [
                $header->invoice_code,
                optional($header->invoice_date)->format('Y-m-d'),
                trim(($header->customer->osa_code ?? '').' - '.($header->customer->business_name ?? '')),
                trim(($header->salesman->osa_code ?? '').' - '.($header->salesman->name ?? '')),
                trim(($header->warehouse->warehouse_code ?? '').' - '.($header->warehouse->warehouse_name ?? '')),
                number_format($header->vat,2),
                number_format($header->excise,2),
                number_format($header->net,2),
                number_format($header->total,2),
            ];
            $rowIndex++;

            // 🔥 DETAIL HEADER
            $rows[] = [
                '',
                'Item','UOM','Price','Qty','Discount','VAT','Net','Total'
            ];
            $rowIndex++;

            $start = $rowIndex - 1;

            // 🔹 DETAILS
            foreach ($details as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '').' - '.($d->item->name ?? '')),
                    $d->uoms->name ?? '',
                    number_format($d->item_price,2),
                    $d->quantity,
                    number_format($d->discount,2),
                    number_format($d->vat,2),
                    number_format($d->net,2),
                    number_format($d->total,2),
                ];
                $rowIndex++;
            }

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
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:I1")->applyFromArray([
                    'fill'=>[
                        'fillType'=>Fill::FILL_SOLID,
                        'startColor'=>['rgb'=>'993442']
                    ],
                    'font'=>[
                        'bold'=>true,
                        'color'=>['rgb'=>'FFFFFF']
                    ],
                ]);

                for ($i=2;$i<=$lastRow;$i++) {
                    if ($sheet->getCell("B{$i}")->getValue()==='Item') {
                        $sheet->getStyle("B{$i}:I{$i}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$i}:I{$i}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

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