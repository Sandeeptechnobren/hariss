<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InvoiceFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        $export = [];

        foreach ($this->rows as $row) {
            $export[] = [
                'Header ID'        => (string) ($row->header_id ?? ''),
                'SAP ID'           => (string) ($row->sap_id ?? ''),
                'Warehouse Code'   => (string) ($row->warehouse_code ?? ''),
                'Warehouse Name'   => (string) ($row->warehouse_name ?? ''),
                'Invoice Code'     => (string) ($row->invoice_code ?? ''),
                'Invoice Date'     => (string) ($row->invoice_date ?? ''),

                'Item Category'    => (string) ($row->item_category_dll ?? ''),
                'Item Name'        => (string) ($row->name ?? ''),
                'ERP Code'         => (string) ($row->erp_code ?? ''),

                'Quantity'         => (float)  ($row->quantity ?? 0),

                'Base UOM Vol'     => (float)  ($row->base_uom_vol_calc ?? 0),
                'Alt UOM Vol'      => (float)  ($row->alter_base_uom_vol_calc ?? 0),

                'Total Amount'     => (float)  ($row->total_amount ?? 0),
            ];
        }

        return new Collection($export);
    }

    public function headings(): array
    {
        return [
            'Header ID',
            'SAP ID',
            'Warehouse Code',
            'Warehouse Name',
            'Invoice Code',
            'Invoice Date',
            'Item Category',
            'Item Name',
            'ERP Code',
            'Quantity',
            'Base UOM Vol',
            'Alt UOM Vol',
            'Total Amount',
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
                        'color' => ['rgb' => 'FFFFFF'],
                        'size'  => 12,
                        'name'  => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4A4A4A'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Increase header height
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
