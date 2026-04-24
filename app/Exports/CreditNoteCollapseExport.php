<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use App\Helpers\CommonLocationFilter;
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
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $request = $this->request;

        $data = [];
        $row = 2;

        // 🔥 FILTER ARRAY
        $filter = $request->input('filter', []);

        $fromDate = $filter['from_date'] ?? null;
        $toDate   = $filter['to_date'] ?? null;
        $distributorId = $filter['distributor_id'] ?? null;

        // 🔥 QUERY
        $query = CreditNoteHeader::with([
            'customer',
            'distributor',
            'purchasereturn',
            'salesman',
            'creditNoteDetails.item'
        ]);

        // ✅ DATE FILTER
        if ($fromDate && $toDate) {
            $query->whereDate('created_at', '>=', $fromDate)
                  ->whereDate('created_at', '<=', $toDate);
        }

        // 🔥 PRIORITY LOGIC
        if (!empty($distributorId)) {

            // 👉 direct distributor filter
            $query->where('distributor_id', $distributorId);

        } else {

            // 👉 helper filter
            $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filter);

            if (!empty($warehouseIds)) {
                $query->whereHas('distributor', function ($q) use ($warehouseIds) {
                    $q->whereIn('id', $warehouseIds);
                });
            }
        }

        $headers = $query->orderBy('id', 'asc')->get();

        // 🔽 DATA BUILD
        foreach ($headers as $header) {

            // HEADER ROW
            $data[] = [
                optional($header->created_at)->format('d M Y'),
                $header->credit_note_no,
                $header->supplier_id,
                optional($header->purchasereturn)->return_code,
                trim(
                    (optional($header->distributor)->warehouse_code ?? '') . ' - ' .
                    (optional($header->distributor)->warehouse_name ?? ''),
                    ' -'
                ),
                trim(
                    (optional($header->customer)->osa_code ?? '') . ' - ' .
                    (optional($header->customer)->business_name ?? ''),
                    ' -'
                ),
               // optional($header->salesman)->name ?? '-',
                $header->batch_no,
                $header->total_net,
                $header->total_vat,
                $header->reason,
                $header->total_amount,

            ];

            $headerRow = $row;
            $row++;

            $start = $row;

            $data[] = ['', 'Item', 'Qty', 'Price', 'Batch_no', 'Net', 'Vat', 'Total'];
            $row++;

            if ($header->creditNoteDetails->count()) {
                foreach ($header->creditNoteDetails as $detail) {

                    $item = $detail->item;

                    $itemText = '-';
                    if ($item) {
                        $code = $item->item_code ?? $item->erp_code ?? '';
                        $name = $item->item_name ?? $item->name ?? '';
                        $itemText = trim($code . ' - ' . $name, ' -');
                    }

                    $data[] = [
                        '',
                        $itemText,
                        $detail->qty,
                        $detail->price,
                        $detail->net,
                        $detail->vat,
                        $detail->total,
                    ];

                    $row++;
                }
            } else {
                $data[] = ['', '-', '-', '-', '-','-','-'];
                $row++;
            }

            $end = $row - 1;

            $this->groupIndexes[] = [
                'header_row' => $headerRow,
                'start' => $start,
                'end' => $end
            ];

            // spacing
            $data[] = ['', '', '', '', '', '', ''];
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
            'Purchase return Code',
            'Distributor',
            'Customer',
           // 'Sale Team',
            'Total_net',
            'Total_vat',
            'Reason',
            'Total Amount',
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

                    $sheet->getRowDimension($group['header_row'])
                        ->setOutlineLevel(0)
                        ->setVisible(true);

                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false)
                            ->setCollapsed(true);
                    }

                    $sheet->getStyle("B{$group['start']}:H{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);

                // AUTO WIDTH
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // THIN BORDER
                for ($row = 1; $row <= $lastRow; $row++) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")
                    ->applyFromArray([
                        'borders' => [
                            'bottom' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D3D3D3'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}