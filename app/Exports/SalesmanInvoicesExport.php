<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesmanInvoicesExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $salesmanId;

    public function __construct($salesmanId)
    {
        $this->salesmanId = $salesmanId;
    }

    /**
     * DATA
     */
    public function collection()
    {
        $rows = [];

        $invoices = InvoiceHeader::with([
            'order:id,order_code',
            'delivery:id,delivery_code',
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'customer:id,osa_code,name',
            'salesman:id,osa_code,name',
        ])
        ->withCount([
            'details as item_count' // 👈 InvoiceDetail count by header_id
        ])
        ->where('salesman_id', $this->salesmanId)
        ->get();

        foreach ($invoices as $header) {
            $rows[] = [
                $header->invoice_code,
                optional($header->invoice_date)->format('Y-m-d'),
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $header->item_count, // ✅ Item Count from InvoiceDetail
                $header->vat,
                $header->net_total,
                $header->total_amount,
            ];
        }

        return collect($rows);
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
            'Item Count', // 👈 new column
            'VAT',
            'Net Total',
            'Total Amount',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
