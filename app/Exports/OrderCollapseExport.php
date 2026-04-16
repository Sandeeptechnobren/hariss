<?php

namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class OrderCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;
    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate   ?: $today;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }
    public function collection()
    {
        $rows = [];
        $rowIndex = 2;
        $statusMap = [
            1 => 'Order Created',
            2 => 'Delivery Created',
            3 => 'Completed',
        ];
        $query = OrderHeader::with([
            'warehouse',
            'customer',
            'salesman',
            'route',
            'details.item',
            'details.uoms',
        ])
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59',
                ]);
            })
            ->when(
                !empty($this->warehouseIds),
                fn($q) => $q->whereIn('warehouse_id', $this->warehouseIds)
            )
            ->when(
                !empty($this->routeIds),
                fn($q) => $q->whereIn('route_id', $this->routeIds)
            )
            ->when(
                !empty($this->salesmanIds),
                fn($q) => $q->whereIn('salesman_id', $this->salesmanIds)
            )
            ->orderBy('created_at', 'desc');

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $headers = $query->get();
        foreach ($headers as $header) {
            $headerRow = $rowIndex;
            $rows[] = [
                $header->order_code,
                optional($header->created_at)->format('d M Y'),
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                optional($header->delivery_date)->format('d M Y'),
                $header->comment ?? '',
                (float) $header->vat,
                (float) $header->net_amount,
                (float) $header->total,
                $statusMap[$header->order_flag] ?? '-',
                $header->details->count(),
                '',
                '',
                '',
                '',
                '',
                '',
            ];
            $rowIndex++;
            $detailHeadingRow = $rowIndex;
            $rows[] = [
                '',              // A Order Code
                'Item',          // B
                'UOM',           // C
                'Quantity',      // D
                'Item Price',    // E
                'Total',         // F
            ];
            $rowIndex++;
            foreach ($header->details as $detail) {
                $rows[] = [
                    '', // A
                    trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')), // B Item
                    $detail->uoms->name ?? '',     // C UOM
                    (float) $detail->quantity,     // D Qty
                    (float) $detail->item_price,   // E Price
                    (float) $detail->total,        // F Total
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
                $rowIndex++;
            }
            if ($detailHeadingRow + 1 < $rowIndex) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start'      => $detailHeadingRow,
                    'end'        => $rowIndex - 1,
                ];
            }
            $rows[] = array_fill(0, 20, '');
            $rowIndex++;
        }
        return new Collection($rows);
    }
    public function headings(): array
    {
        return [
            'Order Code',
            'Order Date',
            'Distributors',
            'Customer',
            'Sales Team',
            'Route',
            'Delivery Date',
            'Comment',
            'Vat',
            'Net',
            'Total',
            'Status',
            'Total Item '
        ];
    }
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:T1')->getFont()->setBold(true);
        $sheet->getStyle('A1:T1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();
    //             $lastColumn = $sheet->getHighestColumn();
    //             $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
    //                 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    //                 'alignment' => [
    //                     'horizontal' => Alignment::HORIZONTAL_CENTER,
    //                     'vertical'   => Alignment::VERTICAL_CENTER,
    //                 ],
    //                 'fill' => [
    //                     'fillType' => Fill::FILL_SOLID,
    //                     'startColor' => ['rgb' => '993442'],
    //                 ],
    //                 'borders' => [
    //                     'allBorders' => ['borderStyle' => Border::BORDER_THIN],
    //                 ],
    //             ]);
    //             foreach ($this->groupIndexes as $group) {
    //                 $sheet->getRowDimension($group['header_row'])
    //                     ->setOutlineLevel(0)
    //                     ->setVisible(true);
    //                 for ($i = $group['start']; $i <= $group['end']; $i++) {
    //                     $sheet->getRowDimension($i)->setOutlineLevel(1);
    //                     $sheet->getRowDimension($i)->setVisible(false);
    //                 }
    //             }
    //             $sheet->setShowSummaryBelow(false);
    //         },
    //     ];
    // }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ Main header (A → M)
                $sheet->getStyle("A1:M1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {

                    // ✅ 👉 Detail heading (Item, UOM, Quantity...) ko bold karo
                    $sheet->getStyle("B{$group['start']}:F{$group['start']}")
                        ->getFont()
                        ->setBold(true);

                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
