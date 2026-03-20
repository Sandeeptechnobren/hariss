<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
use App\Models\Agent_Transaction\LoadDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class LoadCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
    public function collection()
    {
        $rows     = [];
        $rowIndex = 2;

        $query = LoadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'projecttype',
            'salesmantype',
            'details.item',
            'details.Uom',
        ])

            // 📅 Date Filter
            ->when(
                $this->fromDate && $this->toDate,
                fn($q) =>
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59'
                ])
            )

            // 🧑‍💼 Salesman highest priority
            ->when(
                !empty($this->salesmanIds),
                fn($q) =>
                $q->whereIn('salesman_id', $this->salesmanIds)
            )

            // 🚚 Route filter
            ->when(
                empty($this->salesmanIds) && !empty($this->routeIds),
                fn($q) =>
                $q->whereIn('route_id', $this->routeIds)
            )

            // 🏭 Warehouse filter
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
                $header->osa_code,
                optional($header->created_at)->format('d-m-Y'),
                $header->accept_time
                    ? Carbon::parse($header->accept_time)->format('d-m-Y')
                    : '',
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $header->salesmantype->salesman_type_name ?? '',
                $header->projecttype->name ?? '',
                $header->is_confirmed == 1 ? 'SalesTeam Accepted' : 'Waiting For Accept',
                $itemCount,
                '',
                '',
                '',
                '',
                '',
            ];

            $rowIndex++;
            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
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
                    trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->Uom->name ?? '',
                    (float) $detail->qty,
                    (float) $detail->price,
                    $detail->status == 1 ? 'Active' : 'Inactive',
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
            'Load No',
            'Load Date',
            'Accept Date',
            'Warehouse',
            'Route',
            'Salesman',
            'Salesman Type',
            'Project Type',
            'Status',
            'Item Count',
            'Item',
            'UOM',
            'Quantity',
            'Price',
            'Detail Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet      = $event->sheet->getDelegate();
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
