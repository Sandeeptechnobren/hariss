<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\UnloadDetail;
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
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class UnloadCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
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
    protected array $columns = [
        'OSA Code',
        'Unload No',
        'Unload Date',
        'Unload Time',
        'Warehouse',
        'Route',
        'Salesman',
        'Load Date',
        // 'Status',
        'Item',
        'UOM',
        'Quantity',
        // 'Detail Status',
    ];

    protected function emptyRow(): array
    {
        return array_fill_keys($this->columns, '');
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = UnloadHeader::with(['warehouse', 'route', 'salesman'])
            ->when(
                $this->fromDate && $this->toDate,
                fn($q) =>
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59'
                ])
            )

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

            $headerRowIndex = $rowIndex;
            $rows[] = [
                'OSA Code'    => (string) ($header->osa_code ?? ''),
                'Unload No'   => (string) ($header->unload_no ?? ''),

                'Unload Date' => $header->unload_date
                    ? Carbon::parse($header->unload_date)->format('d M Y')
                    : '',

                'Unload Time' => $header->unload_time
                    ? Carbon::parse($header->unload_time)->format('h:i A')
                    : '',

                'Warehouse' => trim(
                    ($header->warehouse->warehouse_code ?? '') . ' - ' .
                        ($header->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($header->route->route_code ?? '') . ' - ' .
                        ($header->route->route_name ?? '')
                ),

                'Salesman' => trim(
                    ($header->salesman->osa_code ?? '') . ' - ' .
                        ($header->salesman->name ?? '')
                ),

                'Load Date' => (string) ($header->load_date ?? ''),
                // 'Status'    => $header->status == 1 ? 'Active' : 'Inactive',
                'Item'          => '',
                'UOM'           => '',
                'Quantity'      => '',
                // 'Detail Status' => '',
            ];

            $rowIndex++;
            $details = UnloadDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'OSA Code'    => '',
                    'Unload No'   => '',
                    'Unload Date' => '',
                    'Unload Time' => '',
                    'Warehouse'   => '',
                    'Route'       => '',
                    'Salesman'    => '',
                    'Load Date'   => '',
                    // 'Status'      => '',
                    'Item' => trim(
                        ($detail->item->erp_code ?? '') . ' - ' .
                            ($detail->item->name ?? '')
                    ),

                    'UOM'      => (string) ($detail->uoms->name ?? ''),
                    'Quantity' => (float) ($detail->qty ?? 0),
                    // 'Detail Status' => $detail->status == 1 ? 'Active' : 'Inactive',
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }
            if ($detailRowIndexes) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }
            $rows[] = $this->emptyRow();
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()
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
