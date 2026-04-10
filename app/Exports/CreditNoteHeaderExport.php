<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class CreditNoteHeaderExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    public function collection()
    {
        return CreditNoteHeader::with([
            'customer',
            'distributor',
            'purchaseInvoice',
            'salesman'
        ])->get()->map(function ($item) {
            return [
                optional($item->created_at)->format('d M Y'),
                $item->credit_note_no,
                $item->supplier_id,
                optional($item->purchaseInvoice)->invoice_code,
                optional($item->distributor)->warehouse_code . ' - ' .
                optional($item->distributor)->warehouse_name,
                optional($item->customer)->osa_code . ' - ' .
                optional($item->customer)->business_name,
                optional($item->salesman)->name ?? '-',
                $item->total_amount,
                $item->reason,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Code',
            'SAP ID',
            'Purchase Invoice Code',
            'Distributor',
            'Customer',
            'Sale Team',
            'Total Amount',
            'Reason',
        ];
    }

    // ✅ Header Styling
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // row 1 = header
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'], // white text
                ],
            ],
        ];
    }

    // ✅ Background color + alignment
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // 🎨 Header Background Color (Maroon)
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => '8B1E2D' // 👈 ye tera maroon/red color
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // ✅ Auto column width
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}