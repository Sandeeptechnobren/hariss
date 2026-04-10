<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use App\Models\Hariss_Transaction\Web\HTDeliveryDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HtDeliveryCollapseExport implements
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
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
        $this->fromDate = $fromDate ?: now()->subYear()->toDateString();
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
            'Delivery Code','Delivery Date','PurchaseOrder Code','Order Code',
            'Customer','Salesman','VAT','Excise','Net','Total','Comment'
        ];
        $rowIndex++;

        // 🔥 HEADERS (FILTERED + LIGHT)
        $headers = HTDeliveryHeader::with([
                //'customer:id,osa_code,name',
                'customer:id,osa_code,business_name',
                'salesman:id,osa_code,name',
                'poorder:id,order_code',
                'order:id,order_code'
            ])
            ->whereBetween('delivery_date', [$this->fromDate, $this->toDate])
            ->when($this->warehouseIds, fn($q)=>$q->whereIn('warehouse_id',$this->warehouseIds))
            ->when($this->salesmanIds, fn($q)=>$q->whereIn('salesman_id',$this->salesmanIds))
            ->get();

        // 🔥 DETAILS BULK LOAD (FAST 🚀)
        $allDetails = HTDeliveryDetail::with([
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
                $header->delivery_code,
                optional($header->delivery_date)->format('Y-m-d'),
                $header->poorder->order_code ?? '',
                $header->order->order_code ?? '',
                trim(($header->customer->osa_code ?? '').' - '.($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '').' - '.($header->salesman->name ?? '')),
                $header->vat ? number_format($header->vat,2) : '',
                $header->excise ? number_format($header->excise,2) : '',
                $header->net ? number_format($header->net,2) : '',
                $header->total ? number_format($header->total,2) : '',
                $header->comment ?? '',
            ];
            $rowIndex++;

            // 🔥 DETAIL HEADER (niche show hoga)
            $rows[] = [
                '',
                'Item','UOM Name','Item Price','Quantity','Net Detail','Excise Detail','Detail VAT','Detail Total'
            ];
            $rowIndex++;

            // ✅ GROUP START (header include)
            $start = $rowIndex - 1;

            // 🔹 DETAILS
            foreach ($details as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '').' - '.($d->item->name ?? '')),
                    $d->uoms->name ?? '',
                    number_format($d->item_price,2),
                    $d->quantity,
                    $d->net ? number_format($d->net,2) : '',
                    $d->excise ? number_format($d->excise,2) : '',
                    $d->vat ? number_format($d->vat,2) : '',
                    $d->total ? number_format($d->total,2) : '',
                ];
                $rowIndex++;
            }

            // 🔹 GROUP APPLY
            if ($details->count()) {
                $this->groupIndexes[] = [
                    'start'=>$start,
                    'end'=>$rowIndex-1
                ];
            }

            // GAP
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
                        $sheet->getStyle("B{$i}:I{$i}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$i}:I{$i}")
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