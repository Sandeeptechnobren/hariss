<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\TempReturnH;
use App\Models\Hariss_Transaction\Web\TempReturnD;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TempReturnCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $from;
    protected $to;
    protected $groups = [];

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $headers = TempReturnH::with(['customer'])
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('created_at', '<=', $this->to))
            ->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;
            $rows[] = [
                $header->return_code,
                $header->customer->osa_code ?? '',
                $header->customer->business_name ?? '',
                $header->vat,
                $header->net,
                $header->amount,
                $header->truckname,
                $header->truckno,
                $header->contactno,
                $header->sap_id,
                $header->message,

                '', '', '', '', '', '', '', '', '', '', '', '', ''
            ];

            $rowIndex++;
            $details = TempReturnD::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $d) {

                $rows[] = [
                    '', '', '', '', '', '', '', '', '', '', '',  

                    $d->poshr,
                    $d->item->erp_code ?? '',
                    $d->item->name ?? '',
                    $d->item_value,
                    $d->vat,
                    $d->uom->name ?? '',
                    $d->qty,
                    $d->net,
                    $d->total,
                    $d->expiry_batch,
                    $d->batchno,
                    $d->actual_expiry_date,
                    $d->remark,
                    $d->invoice_sap_id,
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }
            if (count($detailRowIndexes) > 0) {
                $this->groups[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Return Code',
            'Customer Code',
            'Customer Name',
            'Customer Town',
            'VAT',
            'Net',
            'Amount',
            'Truck Name',
            'Truck No',
            'Contact No',
            'SAP ID',
            'Message',

            'POSHR',
            'Item Code',
            'Item Name',
            'Item Value',
            'Detail VAT',
            'UOM Name',
            'Qty',
            'Net',
            'Total',
            'Expiry Batch',
            'Batch No',
            'Actual Expiry Date',
            'Remark',
            'Invoice SAP ID'
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
                    'bold' => true,
                    'color' => ['rgb' => 'F5F5F5'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '993442'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getRowDimension(1)->setRowHeight(25);

            foreach ($this->groups as $g) {
                for ($i = $g['start']; $i <= $g['end']; $i++) {
                    $sheet->getRowDimension($i)
                          ->setOutlineLevel(1)
                          ->setVisible(false);
                }
            }

            $sheet->setShowSummaryBelow(false);
        }
    ];
}
}
