<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ItemWiseInvoiceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected int $itemId;
    protected int $rowIndex = 2; 

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    
    public function collection()
    {
        $rows = [];

        $details = InvoiceDetail::query()
            ->with([
                'header.company',
                'header.order',
                'header.delivery',
                'header.warehouse',
                'header.route',
                'header.customer',
                'header.salesman',
                'item',
                'itemuom',
                'promotion',
                'approver',
            ])
            ->where('item_id', $this->itemId)
            ->whereNull('invoice_details.deleted_at')
            ->whereHas('header', fn($q) => $q->whereNull('deleted_at'))
            ->orderBy('header_id')
            ->get()
            ->groupBy('header_id');

        foreach ($details as $headerId => $items) {

            $invoice = $items->first()->header;

            $rows[] = [
                $invoice->invoice_code,
                optional($invoice->invoice_date)->format('Y-m-d'),
                trim(($invoice->warehouse->warehouse_code ?? '') . ' - ' . ($invoice->warehouse->warehouse_name ?? '')),
                trim(($invoice->customer->osa_code ?? '') . ' - ' . ($invoice->customer->name ?? '')),
                trim(($invoice->salesman->osa_code ?? '') . ' - ' . ($invoice->salesman->name ?? '')),
                

                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];

            foreach ($items as $detail) {
                $rows[] = [
                    '',
                    '',
                    '',
                    '',
                    '',

                    trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->itemuom->name ?? '',
                    $detail->quantity,
                    $detail->itemvalue,
                    $detail->vat,
                    $detail->net_total,
                    $detail->item_total,
                ];
            }
        }

        return new Collection($rows);
    }
    public function headings(): array
    {
        return [
            'Invoice Code',
            'Invoice Date',
            'Warehouse',
            'Customer',
            'Salesman',
            

            'Item',
            'UOM Name',
            'Quantity',
            'Item Value',
            'VAT (Detail)',
            'Net Total (Detail)',
            'Item Total',
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
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(26);
                $row = 2;

        foreach (
            InvoiceDetail::where('item_id', $this->itemId)
                ->whereNull('deleted_at')
                ->get()
                ->groupBy('header_id') as $group
        ) {

            $parentRow = $row;

            // Parent row (not collapsed yet)
            $sheet->getRowDimension($parentRow)
                ->setOutlineLevel(0);

            $row++;

            // Detail rows
            foreach ($group as $detail) {
                $sheet->getRowDimension($row)
                    ->setOutlineLevel(1)
                    ->setVisible(false);
                $row++;
            }

            // ✅ Collapse AFTER children exist
            $sheet->getRowDimension($parentRow)
                ->setCollapsed(true);
        }

        $sheet->setShowSummaryBelow(true);
        $sheet->setShowSummaryRight(false);
            },
        ];
    }
}
