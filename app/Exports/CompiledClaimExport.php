<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CompiledClaimExport implements FromArray, WithHeadings, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            "Claim Period",
            "Warehouse",
            "Approved Qty (CSE)",
            "Approved Claim Amount",
            "Rejected Qty (CSE)",
            "Rejected Amount",
            "ASM",
            "RSM",
            "Status",
        ];
    }

    public function array(): array
    {
        return $this->data->map(function ($item) {

            $warehouse = '';

            if (!empty($item->warehouse)) {
                $warehouse = trim(
                    ($item->warehouse->warehouse_code ?? '') . ' - ' .
                        ($item->warehouse->warehouse_name ?? ''),
                    ' -'
                );
            }

            $monthRange = \Carbon\Carbon::parse($item->start_date)->format('j') . '-' .
                \Carbon\Carbon::parse($item->end_date)->format('j') .
                \Carbon\Carbon::parse($item->start_date)->format('F');

            return [
                $monthRange,
                $warehouse,
                $item->approved_qty_cse ?? 0,
                $item->approved_claim_amount ?? 0,
                $item->rejected_qty_cse ?? 0,
                $item->rejected_amount ?? 0,
                $item->asm_name ?? '',
                $item->rsm_name ?? '',
                $item->approval_status ?? '', // ✅ added (no change in flow)
            ];
        })->toArray();
    }

    // 🎨 HEADER STYLE
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
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], // 🔥 better green
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // row height
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
