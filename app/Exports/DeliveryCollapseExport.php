<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AgentDeliveryHeaders;
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

class DeliveryCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;
    protected array $groupIndexes = [];
    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate   ?: $today;
        $this->warehouseIds = is_array($warehouseIds) ? $warehouseIds : [];
        $this->routeIds     = is_array($routeIds) ? $routeIds : [];
        $this->salesmanIds  = is_array($salesmanIds) ? $salesmanIds : [];
    }
    private function excelSafe($value)
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }
    public function collection()
    {
        $rows = [];
        $rowIndex = 2;
        $query = AgentDeliveryHeaders::select([
                'id',
                'delivery_code',
                'created_at',
                'warehouse_id',
                'route_id',
                'salesman_id',
                'customer_id',
                'vat',
                //'discount',
                'net_amount',
                'total'
            ])
            ->with([
                'warehouse:id,warehouse_code,warehouse_name',
                'route:id,route_code,route_name',
                'salesman:id,osa_code,name',
                'customer:id,osa_code,name',
                'details:id,header_id,item_id,uom_id,item_price,quantity,vat,net_total,total',
                'details.item:id,erp_code,name',
                'details.Uom:id,name',
            ])
            ->when($this->fromDate && $this->toDate, function ($q) {
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59'
                ]);
            })
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
            )
            ->orderBy('created_at', 'desc');

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

            $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {
                foreach ($headers as $header) {
                    $details   = $header->details ?? collect();
                    $itemCount = $details->count();
                    $headerRow = $rowIndex;
                    $rows[] = [
                        $this->excelSafe($header->delivery_code ?? ''),
                        optional($header->created_at)->format('d-M-Y'),
                        $this->excelSafe(
                            trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? ''))
                        ),
                        $this->excelSafe(
                            trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? ''))
                        ),
                        (float) ($header->vat ?? 0),
                     //   (float) ($header->discount ?? 0),
                        (float) ($header->net_amount ?? 0),
                        (float) ($header->total ?? 0),
                        $itemCount,
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
                    $detailHeadingRow = $rowIndex;
                    $rows[] = [
                        '',
                        'Item',
                        'UOM',
                        'Quantity',
                        'Item Price',
                        'VAT',
                       // 'Discount',
                        'Net Amount',
                        'Total',
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
                    foreach ($details as $detail) {
                        $rows[] = [
                            '',
                            $this->excelSafe(
                                trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? ''))
                            ),
                            $detail->Uom->name ?? '',
                            (float) ($detail->quantity ?? 0),
                            (float) ($detail->item_price ?? 0),
                            (float) ($detail->vat ?? 0),
                         //   (float) ($detail->discount ?? 0),
                            (float) ($detail->net_total ?? 0),
                            (float) ($detail->total ?? 0),
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
                    $rows[] = array_fill(0, 18, '');
                    $rowIndex++;
                }
            });

        return new Collection($rows);
    }
    public function headings(): array
    {
        return [
            'Delivery Code',
            'Delivery Date',
            'Distributors',
            'Route',
            'Sales Team',
            'Customer',
            'VAT',
          //  'Discount',
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
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'F5F5F5'],
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
                $sheet->freezePane('A2');
                foreach ($this->groupIndexes as $group) {
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                        $sheet->getStyle("B{$i}")->getAlignment()->setIndent(1);
                    }
                    $sheet->getStyle("B{$group['start']}:I{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
