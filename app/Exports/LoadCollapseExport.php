<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
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

        $query = LoadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'projecttype',
            'salesmantype',
            'details.item',
            'details.Uom',
        ]);


        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->routeIds)) {
            $query->whereIn('route_id', $this->routeIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        // ✅ SAME DATE LOGIC AS GLOBAL
        if (!empty($this->fromDate)) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if (!empty($this->toDate)) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $headers = $query->get();

        foreach ($headers as $header) {

            $details = $header->details;
            $headerRow = $rowIndex;

            // ✅ HEADER
            $rows[] = [
                $header->osa_code,
                optional($header->created_at)->format('d M Y'),
                $header->accept_time ? Carbon::parse($header->accept_time)->format('d M Y') : '',
                $header->accept_time ? Carbon::parse($header->accept_time)->format('h:i A') : '',
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $header->salesmantype->salesman_type_name ?? '',
                $header->projecttype->name ?? '',
                $header->is_confirmed == 1 ? 'SalesTeam Accepted' : 'Waiting For SalesTeam Accepted',
                $details->count(),
                '',
                '',
                '',
                '',
                ''
            ];
            $rowIndex++;

            // ✅ DETAIL HEADING
            $detailHeadingRow = $rowIndex;
            $rows[] = [
                '',
                'Item',
                'UOM',
                // 'Price',
                'Quantity',
                // 'Status',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ];
            $rowIndex++;

            // ✅ DETAILS
            foreach ($details as $detail) {
                $rows[] = [
                    '',
                    trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->Uom->name ?? '',
                    // (float) $detail->price,
                    (float) $detail->qty,
                    // $detail->status == 1 ? 'Active' : 'Inactive',
                    '',
                    '',
                    '',
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
                    'heading_row' => $detailHeadingRow,
                    'start' => $headerRow + 1,
                    'end'   => $rowIndex - 1,
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
            'Load No',
            'Load Date',
            'Accept Date',
            'Accept Time',
            'Distributors',
            'Route',
            'Sales Team',
            'Salesman Team Type',
            'Role Project',
            'Status',
            'Total Item',
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
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // ✅ HEADER STYLE
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->freezePane('A2');

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

                        $sheet->getStyle("B{$i}")
                            ->getAlignment()
                            ->setIndent(1);
                    }

                    // heading hidden
                    $sheet->getRowDimension($group['heading_row'])
                        ->setOutlineLevel(1)
                        ->setVisible(false);

                    // heading bold
                    $sheet->getStyle("B{$group['heading_row']}:F{$group['heading_row']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
                $sheet->setShowSummaryRight(false);
            },
        ];
    }
}
