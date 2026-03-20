<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeInReturn;
use App\Models\Agent_Transaction\ExchangeInInvoice;
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

class ExchangeCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected array $groupIndexes = [];

    /* ================= Excel Formula Safety ================= */
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

        $query = ExchangeHeader::select([
                'id','exchange_code','warehouse_id','customer_id','comment','status'
            ])
            ->with([
                'warehouse:id,warehouse_name',
                'customer:id,osa_code,name',
            ])
            ->orderBy('id', 'desc');

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
        $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

                foreach ($headers as $header) {

                    $headerRow = $rowIndex;

                    /* ================= HEADER ROW ================= */
                    $rows[] = [
                        $this->excelSafe($header->exchange_code),
                        $this->excelSafe($header->warehouse->warehouse_name ?? ''),
                        $this->excelSafe($header->customer->osa_code ?? ''),
                        $this->excelSafe($header->customer->name ?? ''),
                        $this->excelSafe($header->comment ?? ''),
                        $header->status == 1 ? 'Active' : 'Inactive',

                        // padding
                        '', '', '', '', '', '', '', '', '', '',
                    ];

                    $rowIndex++;

                    /* ================= COLLECT SECTION ================= */
$collects = ExchangeInReturn::with(['item:id,code,name', 'uoms:id,name'])
    ->where('header_id', $header->id)
    ->get();

if ($collects->isNotEmpty()) {

    $sectionRows[] = $rowIndex; // Return label row
    $rows[] = [
        'Collect', // A
        '', '', '', '', '', '', '', '', '',
        '', '', '', '', '', '',
    ];
    $rowIndex++;

$detailHeadingRow = $rowIndex;
    $rows[] = [
        '',          // A
        'Item Code', // B
        'Item Name', // C
        'UOM',       // D
        'Price',     // E
        'Quantity',  // F
        'Total',     // G
        'Return Type',// H
        'Region',    // I
        'Status',    // J
        '', '', '', '', '', '',
    ];
    $rowIndex++;

    foreach ($collects as $detail) {
        $rows[] = [
            '', // A
            $this->excelSafe($detail->item->code ?? ''),
            $this->excelSafe($detail->item->name ?? ''),
            $detail->uoms->name ?? '',
            (float) $detail->item_price,
            (float) $detail->item_quantity,
            (float) $detail->total,
            $detail->return_type ?? '',
            $detail->region ?? '',
            $detail->status == 1 ? 'Active' : 'Inactive',
            '', '', '', '', '', '',
        ];
        $rowIndex++;
    }
}
                   /* ================= RETURN SECTION ================= */
$returns = ExchangeInInvoice::with(['item:id,code,name', 'uoms:id,name'])
    ->where('header_id', $header->id)
    ->get();

if ($returns->isNotEmpty()) {

    $sectionRows[] = $rowIndex; // Return label row
    $rows[] = [
        'Return', // A
        '', '', '', '', '', '', '', '', '',
        '', '', '', '', '', '',
    ];
    $rowIndex++;

$detailHeadingRow = $rowIndex;
    $rows[] = [
        '',          // A
        'Item Code', // B
        'Item Name', // C
        'UOM',       // D
        'Price',     // E
        'Quantity',  // F
        'Total',     // G
        '',          // H
        '',          // I
        'Status',    // J
        '', '', '', '', '', '',
    ];
    $rowIndex++;

    foreach ($returns as $detail) {
        $rows[] = [
            '', // A
            $this->excelSafe($detail->item->code ?? ''),
            $this->excelSafe($detail->item->name ?? ''),
            $detail->uoms->name ?? '',
            (float) $detail->item_price,
            (float) $detail->item_quantity,
            (float) $detail->total,
            '',
            '',
            $detail->status == 1 ? 'Active' : 'Inactive',
            '', '', '', '', '', '',
        ];
        $rowIndex++;
    }
}
                    /* ================= GROUP COLLAPSE ================= */
                    if ($rowIndex > $headerRow + 1) {
                        $this->groupIndexes[] = [
                            'header_row' => $headerRow,
                            'start'      => $headerRow + 1,
                            'end'        => $rowIndex - 1,
                            'detail_headings'   => [$detailHeadingRow], 
                            'section_rows'    => $sectionRows,
                        ];
                    }

                    /* ================= BLANK ROW ================= */
                    $rows[] = array_fill(0, 16, '');
                    $rowIndex++;
                }
            });

        return new Collection($rows);
    }

    /* ================= HEADER ONLY ================= */
    public function headings(): array
    {
        return [
            'Exchange Code',
            'Warehouse Name',
            'Customer Code',
            'Customer Name',
            'Comment',
            'Status',
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
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->freezePane('A2');

                foreach ($this->groupIndexes as $group) {

                    // Header always visible
                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    // Collapse details
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);

                        $sheet->getStyle("B{$i}")
                            ->getAlignment()
                            ->setIndent(1);
                    }

                    // ✅ Bold section rows (Collect / Return)
                    foreach ($group['section_rows'] as $row) {
                        $sheet->getStyle("A{$row}")
                            ->getFont()
                            ->setBold(true);
                    }

                    // ✅ Bold ONLY detail heading rows
                    foreach ($group['detail_headings'] as $row) {
                        $sheet->getStyle("B{$row}:J{$row}")
                            ->getFont()
                            ->setBold(true);
                    }
                }
                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
