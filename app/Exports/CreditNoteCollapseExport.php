<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CreditNoteCollapseExport implements FromCollection, WithHeadings, WithEvents
{
    protected $groupIndexes = [];

    public function collection()
    {
        $data = [];
        $row = 2;

        $headers = CreditNoteHeader::with([
            'customer',
            'distributor',
            'purchaseInvoice',
            'salesman',
            'creditNoteDetails.item'
        ])->orderBy('id', 'asc')->get();

        foreach ($headers as $header) {

            // ✅ HEADER ROW
            $data[] = [
                optional($header->created_at)->format('d M Y'),
                $header->credit_note_no,
                $header->supplier_id,
                optional($header->purchaseInvoice)->invoice_code,
                optional($header->distributor)->warehouse_code . ' - ' .
                optional($header->distributor)->warehouse_name,
                optional($header->customer)->osa_code . ' - ' .
                optional($header->customer)->business_name,
                optional($header->salesman)->name ?? '-',
                $header->total_amount,
                $header->reason,
            ];

            $headerRow = $row;
            $row++;

            // ✅ DETAIL HEADER (Code ke niche)
            $start = $row;

            $data[] = [
                '', 'Item', 'Qty', 'Price', 'Total'
            ];
            $row++;

            // ✅ DETAIL ROWS
            if ($header->creditNoteDetails->count()) {
                foreach ($header->creditNoteDetails as $detail) {
                    $data[] = [
                        '',
                        optional($detail->item)->item_code . ' - ' .
                        optional($detail->item)->item_name,
                        $detail->qty,
                        $detail->price,
                        $detail->total,
                    ];
                    $row++;
                }
            } else {
                $data[] = ['', '-', '-', '-', '-'];
                $row++;
            }

            $end = $row - 1;

            // grouping save
            $this->groupIndexes[] = [
                'header_row' => $headerRow,
                'start' => $start,
                'end' => $end
            ];

            // spacing
            $data[] = ['', '', '', '', ''];
            $row++;
        }

        return collect($data);
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // 🎨 HEADER STYLE
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '8B1E2D'],
                    ],
                ]);

                foreach ($this->groupIndexes as $group) {

                    // HEADER visible
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    // 🔥 collapse details
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false)
                            ->setCollapsed(true);
                    }

                    // ✅ DETAIL HEADER BOLD
                    $sheet->getStyle("B{$group['start']}:E{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                // enable +
                $sheet->setShowSummaryBelow(false);

                // auto width
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // borders
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}