<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
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

class InvoiceCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
    protected $from;
    protected $to;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;
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

        $query = InvoiceHeader::select([
                'id',
                'invoice_code',
                'invoice_date',
                'invoice_time',
                'delivery_id',
                'warehouse_id',
                'route_id',
                'customer_id',
                'salesman_id',
                'vat',
                'net_total',
                'total_amount'
            ])
            ->with([
                'delivery:id,delivery_code',
                'warehouse:id,warehouse_code,warehouse_name',
                'route:id,route_code,route_name',
                'customer:id,osa_code,name',
                'salesman:id,osa_code,name',
                'details:id,header_id,item_id,uom,quantity,itemvalue,vat,net_total,item_total',
                'details.item:id,erp_code,name',
                'details.uoms:id,name', 
            ])
            ->when(
                $this->from && $this->to,
                fn($q) => $q->whereBetween('invoice_date', [$this->from, $this->to])
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
            )
            ->orderBy('invoice_date', 'desc');


            $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
            $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

                foreach ($headers as $header) {

                    $details   = $header->details ?? collect();
                    $itemCount = $details->count();
                    $headerRow = $rowIndex;
                    $rows[] = [
                        $this->excelSafe($header->invoice_code),
                        $header->invoice_date
                            ? \Carbon\Carbon::parse($header->invoice_date)->format('d M Y')
                            : '',
                        $header->invoice_time
                            ? \Carbon\Carbon::parse($header->invoice_time)->format('h:i A')
                            : '',
                        $this->excelSafe($header->delivery->delivery_code ?? ''),
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
                        (float) $header->net_total,
                        (float) $header->total_amount,
                        $itemCount,
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
                        'Item Value',
                        'VAT',
                        'Net Total',
                        'Item Total',
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
                        '',
                        '',
                    ];
                    $rowIndex++;
                    foreach ($details as $d) {
                        $rows[] = [
                            '',
                            $this->excelSafe(
                                trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? ''))
                            ),
                            $d->uoms->name ?? '',
                            (float) $d->quantity,
                            (float) $d->item_value,
                            (float) $d->vat,
                            (float) $d->net_total,
                            (float) $d->item_total,
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
                    $rows[] = array_fill(0, 20, '');
                    $rowIndex++;
                }
            });

        return new Collection($rows);
    }
    public function headings(): array
    {
        return [
            'Invoice Code',
            'Invoice Date',
            'Invoice Time',
            'Delivery Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Total',
            'Total Amount',
            'Item Count',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);
                foreach ($this->groupIndexes as $group) {
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                        $sheet->getStyle("B{$i}")->getAlignment()->setIndent(1);
                    }
                    $sheet->getStyle("B{$group['start']}:H{$group['start']}")
                        ->getFont()->setBold(true);
                }
                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
