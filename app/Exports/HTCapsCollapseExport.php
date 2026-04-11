<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtCapsHeader;
use App\Models\Hariss_Transaction\Web\HtCapsDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class HTCapsCollapseExport implements
    FromCollection,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];

    protected $startDate;
    protected $endDate;
    protected $warehouseIds;

    public function __construct(
        $startDate = null,
        $endDate = null,
        $warehouseIds = [],
        $salesmanIds = []
    ) {
        $this->startDate   = $startDate ?: now()->subMonth()->toDateString();
        $this->endDate     = $endDate ?: now()->toDateString();
        $this->warehouseIds = (array) $warehouseIds;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 1;

        $rows[] = [
            'OSA Code',
            'Claim Date',
            'Distributor',
            'Driver',
            'Driver Contact',
            'Truck No',
            'Claim No',
            'Claim Amount'
        ];
        $rowIndex++;

        $query = HtCapsHeader::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'driverinfo:id,osa_code,driver_name,contactno'
        ]);

        if ($this->startDate) {
            $query->whereDate('claim_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('claim_date', '<=', $this->endDate);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->routeIds)) {
            $query->whereIn('route_id', $this->routeIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        $headers = $query->get();

        $allDetails = HtCapsDetail::with([
            'item:id,code,name',
            'uoms:id,name'
        ])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');

        foreach ($headers as $header) {

            $details = $allDetails[$header->id] ?? collect();

            $rows[] = [
                $header->osa_code ?? '',
                $header->claim_date
                    ? Carbon::parse($header->claim_date)->format('d M Y')
                    : '',
                trim(
                    (optional($header->warehouse)->warehouse_code ?? '') .
                        (optional($header->warehouse)->warehouse_name ? ' - ' . optional($header->warehouse)->warehouse_name : '')
                ),
                trim(
                    (optional($header->driverinfo)->osa_code ?? '') .
                        (optional($header->driverinfo)->driver_name ? ' - ' . optional($header->driverinfo)->driver_name : '')
                ),
                optional($header->driverinfo)->contactno ?? '',
                $header->truck_no ?? '',
                $header->claim_no ?? '',

                $header->claim_amount ?? '',
            ];
            $rowIndex++;

            $rows[] = [
                '',
                'Item',
                'Qty',
                'Receive Qty',
                'Receive Amt',
                'Receive Date',
                'Remarks',
                'Remarks2'
            ];
            $rowIndex++;

            $start = $rowIndex - 1;

            foreach ($details as $d) {
                $rows[] = [
                    '',
                    trim(
                        (optional($d->item)->code ?? '') .
                            (optional($d->item)->name ? ' - ' . optional($d->item)->name : '')
                    ),
                    $d->quantity ?? '',
                    $d->receive_qty ?? '',
                    $d->receive_amount ?? '',
                    $d->receive_date
                        ? Carbon::parse($d->receive_date)->format('d M Y')
                        : '',
                    $d->remarks ?? '',
                    $d->remarks2 ?? '',
                ];
                $rowIndex++;
            }

            if ($details->count()) {
                $this->groupIndexes[] = [
                    'start' => $start,
                    'end'   => $rowIndex - 1
                ];
            }

            $rows[] = [''];
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:H1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442']
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                ]);

                for ($i = 2; $i <= $lastRow; $i++) {
                    if ($sheet->getCell("B{$i}")->getValue() === 'Item') {
                        $sheet->getStyle("B{$i}:H{$i}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$i}:H{$i}")
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
