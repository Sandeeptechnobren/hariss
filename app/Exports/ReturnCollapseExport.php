<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ReturnHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class ReturnCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
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

    /* ================= Excel Formula Safety ================= */
    private function excelSafe($value)
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "" . $value;
        }
        return $value;
    }

public function collection()
{
    $rows = [];
    $rowIndex = 2;

    $query = ReturnHeader::select([
        'id',
        'osa_code',
        'order_id',
        'warehouse_id',
        'route_id',
        'customer_id',
        'salesman_id',
        'vat',
        'net_amount',
        'total',
        'created_at'
    ])
        ->with([
            'order:id,order_code', 
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'customer:id,osa_code,name',
            'salesman:id,osa_code,name',
            'details:id,header_id,item_id,uom_id,item_price,item_quantity,vat,net_total,total',
            'details.item:id,erp_code,name',
            'details.uom:id,name',
        ])
        ->when(
            $this->fromDate && $this->toDate,
            fn($q) => $q->whereBetween('created_at', [
                $this->fromDate . ' 00:00:00',
                $this->toDate . ' 23:59:59'
            ])
        )
        ->when(
            !empty($this->salesmanIds),
            fn($q) => $q->whereIn('salesman_id', $this->salesmanIds)
        )
        ->when(
            empty($this->salesmanIds) && !empty($this->routeIds),
            fn($q) => $q->whereIn('route_id', $this->routeIds)
        )
        ->when(
            empty($this->salesmanIds) && empty($this->routeIds) && !empty($this->warehouseIds),
            fn($q) => $q->whereIn('warehouse_id', $this->warehouseIds)
        );

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $query->orderBy('created_at', 'desc')
            ->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

                foreach ($headers as $header) {

                    $details   = $header->details ?? collect();
                    $itemCount = $details->count();
                    $headerRow = $rowIndex;

                    /* ================= HEADER ROW ================= */
                    $rows[] = [
                        $this->excelSafe($header->osa_code ?? ''),
                        $this->excelSafe($header->order->order_code ?? ''),
                        $this->excelSafe(
                            trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? ''))
                        ),
                        (float) $header->vat,
                        (float) $header->net_amount,
                        (float) $header->total,
                        $itemCount,

                        // pad columns
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

                    /* ================= DETAIL HEADING ROW (COLUMN B) ================= */
                    $detailHeadingRow = $rowIndex;

                    $rows[] = [
                        '',            // A
                        'Item',        // B
                        'UOM',         // C
                        'Quantity',    // D
                        'Item Price',  // E
                        'VAT',         // F
                        'Net Total',   // G
                        'Total',       // H
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

                    /* ================= DETAIL ROWS ================= */
                    foreach ($details as $detail) {
                        $rows[] = [
                            '', // A
                            $this->excelSafe(
                                trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? ''))
                            ),
                            $detail->uom->name ?? '',
                            (float) $detail->item_quantity,
                            (float) $detail->item_price,
                            (float) $detail->vat,
                            (float) $detail->net_total,
                            (float) $detail->total,
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

                    /* ================= GROUP DETAILS ================= */
                    if ($detailHeadingRow + 1 < $rowIndex) {
                        $this->groupIndexes[] = [
                            'header_row' => $headerRow,
                            'start'      => $detailHeadingRow,
                            'end'        => $rowIndex - 1,
                        ];
                    }

                    /* ================= BLANK ROW ================= */
                    $rows[] = array_fill(0, 18, '');
                    $rowIndex++;
                }
            });

        return new Collection($rows);
    }

    /* ================= ONLY HEADER HEADINGS ================= */
    public function headings(): array
    {
        return [
            'OSA Code',
            'Order Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Amount',
            'Total',
            'Item Count',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                /* ===== HEADER STYLE ===== */
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'F5F5F5']],
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
                $sheet->freezePane('A2');

                /* ===== COLLAPSE DETAILS ===== */
                foreach ($this->groupIndexes as $group) {

                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                        $sheet->getStyle("B{$i}")->getAlignment()->setIndent(1);
                    }

                    // Bold detail heading
                    $sheet->getStyle("B{$group['start']}:H{$group['start']}")
                        ->getFont()->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
