<?php

namespace App\Exports;

use App\Models\StockTransferHeader;
use App\Models\WarehouseStock;
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
use Carbon\Carbon;

class StockTransferCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $uuid;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $uuid = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->warehouseIds = $warehouseIds;
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $headers = StockTransferHeader::with([
            'sourceWarehouse',
            'destinyWarehouse',
            'details.item'
        ])
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereBetween('transfer_date', [
                    Carbon::parse($this->fromDate)->startOfDay(),
                    Carbon::parse($this->toDate)->endOfDay(),
                ]);
            })
            ->when(
                !empty($this->warehouseIds),
                fn($q) =>
                $q->whereIn('source_warehouse', $this->warehouseIds)
            )
            ->when(
                $this->uuid,
                fn($q) =>
                $q->where('uuid', $this->uuid)
            )
            ->whereNull('deleted_at')
            ->get();

        foreach ($headers as $header) {

            $headerRow = $rowIndex;

            // ✅ HEADER ROW
            $rows[] = [
                $header->osa_code,
                $header->transfer_date
                    ? \Carbon\Carbon::parse($header->transfer_date)->format('d M Y')
                    : '',

                // optional($header->transfer_date)->format('d M Y'),
                trim(($header->sourceWarehouse->warehouse_code ?? '') . ' - ' . ($header->sourceWarehouse->warehouse_name ?? '')),
                trim(($header->destinyWarehouse->warehouse_code ?? '') . ' - ' . ($header->destinyWarehouse->warehouse_name ?? '')),
                $header->details->count(),
                number_format($header->details->sum('transfer_qty'), 2),
                '',
                '',
            ];

            $rowIndex++;

            $stocks = WarehouseStock::whereIn('warehouse_id', [
                $header->source_warehouse,
                $header->destiny_warehouse
            ])
                ->whereIn('item_id', $header->details->pluck('item_id'))
                ->whereNull('deleted_at')
                ->get()
                ->groupBy(['warehouse_id', 'item_id']);
            // ✅ DETAIL HEADING
            $detailHeadingRow = $rowIndex;

            $rows[] = [
                '',
                'Item',
                'Transfer Qty',
                'Parent Stock',   // ✅ add
                'Child Stock',
                '',
                '',
                '',
                '',
            ];

            $rowIndex++;

            // ✅ DETAILS LOOP
            foreach ($header->details as $detail) {
                $fromStock = $stocks[$header->source_warehouse][$detail->item_id][0]->qty ?? 0;
                $toStock   = $stocks[$header->destiny_warehouse][$detail->item_id][0]->qty ?? 0;

                $rows[] = [
                    '',
                    trim(($detail->item->code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    (float) $detail->transfer_qty,
                    $fromStock,
                    $toStock,
                    '',
                    '',
                    '',
                    '',
                ];

                $rowIndex++;
            }

            // ✅ GROUPING (Collapse)
            if ($detailHeadingRow + 1 < $rowIndex) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start'      => $detailHeadingRow,
                    'end'        => $rowIndex - 1,
                ];
            }

            // ✅ GAP ROW
            $rows[] = array_fill(0, 10, '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Transfer No',
            'Date',
            'Parent Warehouse',
            'Child Warehouse',
            'Item Count',
            'Total Qty',
            '',
            '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // ✅ Header Style
                $sheet->getStyle("A1:F1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'F5F5F5']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                ]);

                // ✅ Collapse Logic
                foreach ($this->groupIndexes as $group) {

                    // Header visible
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    // Collapse details
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }

                    // Detail heading bold
                    $sheet->getStyle("B{$group['start']}:E{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
