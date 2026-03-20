<?php

namespace App\Exports;

use App\Models\AgentCustomer;
use App\Models\Agent_Transaction\InvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};

class InvoiceAgentCustomerExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected $uuid;
    protected $from;
    protected $to;
    protected $groupIndexes = [];

    public function __construct($uuid = null, $from = null, $to = null)
    {
        $this->uuid = $uuid;
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;
        $customer = AgentCustomer::where('uuid', trim($this->uuid))->first();
        if (!$customer) {
            return new Collection([]);
        }
        $headers = InvoiceHeader::with([
            'company',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
            'details.item',
            'details.uoms',
            'details.promotion',
        ])
            ->where('customer_id', $customer->id)
            ->when($this->from, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('invoice_date', '<=', $this->to))
            ->get();

        foreach ($headers as $header) {

            $details   = $header->details;
            $itemCount = $details->count();
            $headerRow = $rowIndex;
            $rows[] = [
                $header->invoice_code,
                optional($header->invoice_date)->format('Y-m-d'),
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $itemCount,
                '', '', '', '', '', '', '',
            ];

            $rowIndex++;
            $detailRowIndexes = [];
            foreach ($details as $d) {
                $rows[] = [
                    '', '', '', '', '', '', '', 
                    trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                    $d->uoms->name ?? '',
                    (float) $d->quantity,
                    (float) $d->item_value,
                    (float) $d->vat,
                    (float) $d->net_total,
                    (float) $d->item_total,
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
            'Invoice Code',
            'Invoice Date',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'Item Count',
            'Item',
            'UOM',
            'Quantity',
            'Item Value',
            'VAT (Detail)',
            'Net (Detail)',
            'Item Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
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
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
