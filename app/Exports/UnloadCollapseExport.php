<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
use App\Models\Agent_Transaction\UnloadDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class UnloadCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected array $groupIndexes = [];

    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    protected array $columns = [
        // 'OSA Code',
        'Unload No',
        'Unload Date',
        'Unload Time',
        'Load Date',
        'Distributors',
        'Route',
        'Sales Team',
    ];

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
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

        $query = UnloadHeader::with(['warehouse', 'route', 'salesman']);

        // ✅ DATE FILTER
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->routeIds)) {
            $query->whereIn('route_id', $this->routeIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        // ✅ DATE FILTER (MATCH GLOBAL)
        if (!empty($this->fromDate)) {
            $query->whereDate('unload_date', '>=', $this->fromDate);
        }

        if (!empty($this->toDate)) {
            $query->whereDate('unload_date', '<=', $this->toDate);
        }

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRow = $rowIndex;

            // ✅ HEADER
            $rows[] = [
                // $header->osa_code,
                $header->unload_no,
                $header->unload_date
                    ? \Carbon\Carbon::parse($header->unload_date)->format('d M Y')
                    : '',

                $header->unload_time
                    ? \Carbon\Carbon::parse($header->unload_time)->format('h:i A')
                    : '',
                $header->load_date
                    ? \Carbon\Carbon::parse($header->load_date)->format('d M Y')
                    : '',
                trim(
                    ($header->warehouse->warehouse_code ?? '') . ' - ' .
                        ($header->warehouse->warehouse_name ?? '')
                ),

                trim(
                    ($header->route->route_code ?? '') . ' - ' .
                        ($header->route->route_name ?? '')
                ),

                trim(
                    ($header->salesman->osa_code ?? '') . ' - ' .
                        ($header->salesman->name ?? '')
                ),
                '',
                '',
                ''
            ];
            $rowIndex++;

            // ✅ DETAIL HEADING
            $headingRow = $rowIndex;
            $rows[] = ['', 'Item', 'UOM', 'Quantity', '', '', '', '', '', '', ''];
            $rowIndex++;

            // ✅ DETAILS
            $details = UnloadDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            foreach ($details as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                    $d->uoms->name ?? '',
                    (float)$d->qty,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ];
                $rowIndex++;
            }

            // ✅ GROUP
            if ($details->count()) {
                $this->groupIndexes[] = [
                    'header_row' => $headerRow,
                    'heading_row' => $headingRow,
                    'start' => $headerRow + 1,
                    'end' => $rowIndex - 1,
                ];
            }

            // ✅ GAP
            $rows[] = array_fill(0, 11, '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // ✅ HEADER STYLE
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                ]);

                foreach ($this->groupIndexes as $g) {

                    // collapse
                    for ($i = $g['start']; $i <= $g['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }

                    // heading hidden
                    $sheet->getRowDimension($g['heading_row'])
                        ->setOutlineLevel(1)
                        ->setVisible(false);

                    // heading bold
                    $sheet->getStyle("B{$g['heading_row']}:D{$g['heading_row']}")
                        ->getFont()->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
                $sheet->setShowSummaryRight(false);
            },
        ];
    }
}
