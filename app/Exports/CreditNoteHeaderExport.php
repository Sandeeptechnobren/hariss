<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use App\Helpers\CommonLocationFilter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

class CreditNoteHeaderExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $request = $this->request;

        // 🔥 FILTER ARRAY
        $filter = $request->input('filter', []);

        $fromDate = $filter['from_date'] ?? null;
        $toDate   = $filter['to_date'] ?? null;
        $distributorId = $filter['distributor_id'] ?? null;

        // 🔥 QUERY
        $query = CreditNoteHeader::with([
            'customer',
            'distributor',
            'purchaseInvoice',
            'salesman'
        ]);

        // ✅ DATE FILTER
        if ($fromDate && $toDate) {
            $query->whereDate('created_at', '>=', $fromDate)
                  ->whereDate('created_at', '<=', $toDate);
        }

        // 🔥 PRIORITY LOGIC (IMPORTANT)
        if (!empty($distributorId)) {

            // 👉 direct distributor filter
            $query->where('distributor_id', $distributorId);

        } else {

            // 👉 helper filter only when distributor_id not present
            $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filter);

            if (!empty($warehouseIds)) {
                $query->whereHas('distributor', function ($q) use ($warehouseIds) {
                    $q->whereIn('id', $warehouseIds);
                });
            }
        }

        return $query->orderBy('id', 'desc')->get()->map(function ($item) {
            return [
                optional($item->created_at)->format('d M Y'),
                $item->credit_note_no,
                $item->supplier_id,
                optional($item->purchaseInvoice)->invoice_code,

                // ✅ Distributor combined
                trim(
                    (optional($item->distributor)->warehouse_code ?? '') . ' - ' .
                    (optional($item->distributor)->warehouse_name ?? ''),
                    ' -'
                ),

                // ✅ Customer combined
                trim(
                    (optional($item->customer)->osa_code ?? '') . ' - ' .
                    (optional($item->customer)->business_name ?? ''),
                    ' -'
                ),

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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // 🎨 Header Background
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => '8B1E2D'
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // Auto width
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}