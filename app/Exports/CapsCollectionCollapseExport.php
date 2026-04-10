<?php

namespace App\Exports;

use App\Models\Agent_Transaction\CapsCollectionHeader;
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

class CapsCollectionCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
{
    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        // ✅ FIXED (no auto today)
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;

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
            'customerdata',
            'details.item',
            'details.uom2'
        ])
            ->withSum('details as itemtotal', 'collected_quantity')

            // ✅ DATE FILTER FIX
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereBetween('created_at', [
                    Carbon::parse($this->fromDate)->startOfDay(),
                    Carbon::parse($this->toDate)->endOfDay(),
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
            );

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRow = $rowIndex;

            // ✅ HEADER
            $rows[] = [
                $header->code,
                optional($header->created_at)->format('d M Y'),
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->customerdata->osa_code ?? '') . ' - ' . ($header->customerdata->name ?? '')),
                $header->details->count(),
                $header->itemtotal ?? 0,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];

            $rowIndex++;

            // ✅ DETAIL HEADING
            $detailHeadingRow = $rowIndex;

            $rows[] = [
                '',
                'Item',
                'UOM',
                'Collected Qty',
                'Item Price',
                'Total',
                'Status',
                '',
                '',
                '',
                '',
                '',
                '',
            ];

            $rowIndex++;

            // ✅ DETAILS
            foreach ($header->details as $detail) {

                $rows[] = [
                    '',
                    trim(($detail->item->code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->uom2->name ?? '',
                    (float) $detail->collected_quantity,
                    (float) $detail->price,
                    (float) $detail->total,
                    $detail->status == 1 ? 'Active' : 'Inactive',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];

                $rowIndex++;
            }

            // ✅ GROUP
            if ($detailHeadingRow + 1 < $rowIndex) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'start'      => $detailHeadingRow,
                    'end'        => $rowIndex - 1,
                ];
            }

            // ✅ GAP
            $rows[] = array_fill(0, 20, '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Caps Collection Code',
            'Date',
            'Distributer',
            'Customer',
            'Item Count',
            'Item Qty Total',
            '',
            '',
            '',
            '',
            '',
            '',
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

                // ✅ HEADER COLOR ONLY A → G
                $sheet->getStyle("A1:G1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {

                    // header visible
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    // collapse
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }

                    // ✅ DETAIL HEADING BOLD
                    $sheet->getStyle("B{$group['start']}:G{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
