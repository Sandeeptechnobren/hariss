<?php

namespace App\Exports;

use App\Models\Agent_Transaction\CapsCollectionHeader;
use App\Models\Agent_Transaction\CapsCollectionDetail;
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

class CapsCollectionCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected $from;
    protected $to;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;
    protected $groupIndexes = [];

    public function __construct($from = null, $to = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->from = $from ?: $today;
        $this->to   = $to   ?: $today;
        // $this->from = $from;
        // $this->to   = $to;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = CapsCollectionHeader::with([
            'warehouse',
            'route',
            'salesman',
            'customerdata'
        ])
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('created_at', '<=', $this->to))

            // 🔥 ADDED FILTERS (no change to existing logic)
            ->when(
                !empty($this->salesmanIds),
                fn($q) =>
                $q->whereIn('salesman_id', $this->salesmanIds)
            )

            ->when(
                empty($this->salesmanIds) && !empty($this->routeIds),
                fn($q) =>
                $q->whereIn('route_id', $this->routeIds)
            )

            ->when(
                empty($this->salesmanIds) && empty($this->routeIds) && !empty($this->warehouseIds),
                fn($q) =>
                $q->whereIn('warehouse_id', $this->warehouseIds)
            );

            $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
            $headers = $query->get();

        foreach ($headers as $header) {

            $details   = $header->details;
            $itemCount = $details->count();
            $headerRow = $rowIndex;

            $rows[] = [
                $header->code,
                trim(($header->warehouse->warehouse_code ?? '') . '-' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->customerdata->osa_code ?? '') . '-' . ($header->customerdata->name ?? '')),
                $itemCount,
                '',
                '',
                '',
                '',
            ];

            $rowIndex++;
            $detailRowIndexes = [];

            foreach ($details as $d) {

                $rows[] = [
                    '',
                    '',
                    '',
                    '',
                    trim(($d->item->code ?? '') . '-' . ($d->item->name ?? '')),
                    $d->uom2->name ?? '',
                    (float) $d->collected_quantity,
                    $d->status == 1 ? 'Active' : 'Inactive',
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }

            if (!empty($detailRowIndexes)) {
                $this->groupIndexes[] = [
                    'start' => $headerRow + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            $rows[] = array_fill(0, count($rows[0]), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }
    public function headings(): array
    {
        return [
            'OSA Code',
            'Warehouse',
            'Customer',
            'Item Count',
            'Item',
            'UOM',
            'Collected Qty',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
                foreach ($this->groupIndexes as $group) {
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
