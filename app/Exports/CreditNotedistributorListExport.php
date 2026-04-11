<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\CreditNoteHeader;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;

class CreditNotedistributorListExport implements FromCollection, WithHeadings, WithEvents
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $filter = $this->request->input('filter', []); // ✅ added

        $query = CreditNoteHeader::with([
            'customer:id,business_name,osa_code',
            'salesman:id,name',
            'distributor:id,uuid,warehouse_name,warehouse_code',
            'purchaseInvoice:id,invoice_code'
        ]);

        // ✅ Distributor UUID filter
        if (!empty($filter['distributor_uuid'])) {
            $uuid = trim($filter['distributor_uuid']);

            $query->whereHas('distributor', function ($q) use ($uuid) {
                $q->where('uuid', $uuid);
            });
        }

        // ✅ Date filter
        if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
            $query->whereBetween('created_at', [
                $filter['from_date'],
                $filter['to_date']
            ]);
        }

        return $query->orderBy('id', 'asc')->get()->map(function ($item) {
            return [
                $item->id,
                $item->credit_note_no,
                optional($item->purchaseInvoice)->invoice_code,
                $item->supplier_id,
                $item->total_amount,
                $item->reason,
                $item->status,

                // Customer
                trim(
                    (optional($item->customer)->osa_code ?? '') . ' - ' .
                    (optional($item->customer)->business_name ?? ''),
                    ' -'
                ),

                optional($item->salesman)->name,

                // Distributor
                trim(
                    (optional($item->distributor)->warehouse_code ?? '') . ' - ' .
                    (optional($item->distributor)->warehouse_name ?? ''),
                    ' -'
                ),

                $item->created_at,
                $item->updated_at,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Credit Note No',
            'Invoice Code',
            'Supplier ID',
            'Total Amount',
            'Reason',
            'Status',
            'Customer',
            'Salesman',
            'Distributor',
            'Created At',
            'Updated At',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Header Style
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '8B1E2D'
                        ],
                    ],
                ]);

                // Row height
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Auto width
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}