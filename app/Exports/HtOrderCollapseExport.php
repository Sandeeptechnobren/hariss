<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use App\Models\Hariss_Transaction\Web\HTOrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtOrderCollapseExport implements
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $salesmanIds = [])
    {
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
            'Order Code',
            'Order Date',
            'Delivery Date',
            'Customer',
            'Sales Team',
            'VAT',
            'Net Amount',
            'Total Item',
            'Total'
        ];
        $rowIndex++;

        $query = HTOrderHeader::with([
            'customer:id,osa_code,business_name',
            'salesman:id,osa_code,name'
        ]);

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);

        $headers = $query->get();

        $details = HTOrderDetail::with(['item', 'uoms'])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');

        foreach ($headers as $header) {

            $detailList = $details[$header->id] ?? [];
            $count = count($detailList);

            // 🔹 ORDER HEADER ROW
            $rows[] = [
                $header->order_code,
                optional($header->order_date)->format('d M Y'),
                optional($header->delivery_date)->format('d M Y'),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                number_format((float)$header->vat, 2, '.', ','),
                number_format((float)$header->net_amount, 2, '.', ','),
                $count,
                number_format((float)$header->total, 2, '.', ','),
            ];
            $rowIndex++;

            $start = $rowIndex;

            // 🔥 DETAIL HEADER (IMPORTANT)
            $rows[] = [
                '',
                'Item',
                'UOM',
                'Price',
                'Qty',
                'VAT',
                'Excise',
                'Net',
                'Total'
            ];
            $rowIndex++;

            // 🔹 DETAILS
            foreach ($detailList as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                    $d->uoms->name ?? '',
                    number_format((float)$d->item_price, 2, '.', ','),
                    $d->quantity,
                    number_format((float)$d->vat, 2, '.', ','),
                    number_format((float)$d->excise, 2, '.', ','),
                    number_format((float)$d->net, 2, '.', ','),
                    number_format((float)$d->total, 2, '.', ','),
                ];
                $rowIndex++;
            }

            $this->groupIndexes[] = [
                'start' => $start,
                'end' => $rowIndex - 1
            ];

            // 🔻 GAP ROW
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
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // 🔹 HEADER STYLE
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442']
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ],
                ]);

                // 🔥 DETAIL HEADER BOLD
                for ($i = 2; $i <= $lastRow; $i++) {
                    if ($sheet->getCell("B{$i}")->getValue() === 'Item') {
                        $sheet->getStyle("B{$i}:I{$i}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$i}:I{$i}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                // 🔹 GROUP COLLAPSE
                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }
                }

                // ✅ DEFAULT GRIDLINES (IMPORTANT)
                $sheet->setShowGridlines(true);
                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
