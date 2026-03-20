<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{
    FromArray,
    WithHeadings,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReturnExport implements FromArray, WithHeadings, WithEvents
{
    protected Collection $data;
    protected int $rowIndex = 2;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }


    public function headings(): array
    {
        return [
            'Return Code',
            'Warehouse',
            'Customer',
            'Salesman',
            'Return Date',

            'Item Code',
            'Item Name',
            'Item Price',
            'Quantity',
            'VAT',
            'Discount',
            'Gross',
            'Net',
            'Total',

            'Return Type',
            'Return Reason',
            'Promotional',
            'Status',
        ];
    }


    public function array(): array
    {
        $rows = [];

        foreach ($this->data->groupBy('header_id') as $details) {

            $header = $details->first()->returnHeader;

            // 🔹 HEADER ROW (Return Header)
            $rows[] = [
                $header?->osa_code,
                $header?->warehouse?->warehouse_name,
                $header?->customer?->name,
                $header?->salesman?->name,
                optional($header?->created_at)->format('Y-m-d'),

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

            // 🔹 DETAIL ROWS
            foreach ($details as $row) {
                $rows[] = [
                    '',
                    '',
                    '',
                    '',
                    '',

                    $row->item?->erp_code,
                    $row->item?->name,
                    $row->item_price,
                    $row->item_quantity,
                    $row->vat,
                    $row->discount,
                    $row->gross_total,
                    $row->net_total,
                    $row->total,

                    $row->returntype?->return_type,
                    $row->returnreason?->return_reason,
                    $row->is_promotional ? 'Yes' : 'No',
                    $row->status,
                ];
            }
        }

        return $rows;
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /**
                 * 🔹 TABLE HEADER STYLE (ROW 1 ONLY)
                 */
                $sheet->getStyle('A1:R1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // white text
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], // professional dark red
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => ['rgb' => '6E2C00'],
                        ],
                    ],
                ]);

                foreach ($this->data->groupBy('header_id') as $headerGroup) {

                    // Header row
                    $sheet->getRowDimension($this->rowIndex)
                        ->setOutlineLevel(0)
                        ->setCollapsed(false);

                    $this->rowIndex++;

                    // Detail rows
                    foreach ($headerGroup as $detail) {
                        $sheet->getRowDimension($this->rowIndex)
                            ->setOutlineLevel(1)
                            ->setCollapsed(true);

                        $this->rowIndex++;
                    }
                }

                // UX helpers
                $sheet->freezePane('A2');
                $sheet->setShowSummaryBelow(false);

                // Auto width (optional but clean)
                foreach (range('A', 'R') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}
