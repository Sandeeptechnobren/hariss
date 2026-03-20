<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
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

class InvoiceItemExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $itemId;

    public function __construct($itemId)
    {
        $this->itemId = $itemId;
    }

    public function collection()
    {
        $rows = [];

        $headerIds = InvoiceDetail::where('item_id', $this->itemId)
            ->distinct()
            ->pluck('header_id');

        $invoices = InvoiceHeader::with([
            'company',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
        ])->whereIn('id', $headerIds)->get();

        foreach ($invoices as $invoice) {
            $rows[] = [
                // 'Invoice Code'     => (string) $invoice->invoice_code,
                // 'Currency Name'    => (string) ($invoice->company->currency_name ?? ''),
                // 'Company Name'     => (string) ($invoice->company->name ?? ''),
                // 'Order Code'       => (string) ($invoice->order->order_code ?? ''),
                // 'Delivery Code'    => (string) ($invoice->delivery->delivery_code ?? ''),
                // 'Warehouse Code'   => (string) ($invoice->warehouse->warehouse_code ?? ''),
                // 'Warehouse Name'   => (string) ($invoice->warehouse->warehouse_name ?? ''),
                // 'Route Code'       => (string) ($invoice->route->route_code ?? ''),
                // 'Route Name'       => (string) ($invoice->route->route_name ?? ''),
                // 'Customer Code'    => (string) ($invoice->customer->osa_code ?? ''),
                // 'Customer Name'    => (string) ($invoice->customer->name ?? ''),
                // 'Salesman Code'    => (string) ($invoice->salesman->osa_code ?? ''),
                // 'Salesman Name'    => (string) ($invoice->salesman->name ?? ''),
                // 'Invoice Date'     => (string) $invoice->invoice_date,
                // 'Invoice Time'     => (string) $invoice->invoice_time,
                // 'Gross Total'      => (float) $invoice->gross_total,
                // 'VAT'              => (float) $invoice->vat,
                // 'Pre VAT'          => (float) $invoice->pre_vat,
                // 'Net Total'        => (float) $invoice->net_total,
                // 'Promotion Total'  => (float) $invoice->promotion_total,
                // 'Discount'         => (float) $invoice->discount,
                // 'Total Amount'     => (float) $invoice->total_amount,
                // 'Status'           => $invoice->status == 1 ? 'Active' : 'Inactive',

                // 'Item Code'             => '',
                // 'Item Name'             => '',
                // 'UOM Code'              => '',
                // 'UOM Name'              => '',
                // 'Quantity'              => '',
                // 'Item Value'            => '',
                // 'VAT (Detail)'          => '',
                // 'Pre VAT (Detail)'      => '',
                // 'Net Total (Detail)'    => '',
                // 'Item Total'            => '',
                // 'Promotion Code'        => '',
                // 'Parent'                => '',
                // 'Approver Name'         => '',
                // 'Approved Date'         => '',
                // 'Rejected By'           => '',
                // 'RM Approver Name'      => '',
                // 'RM Reject Name'        => '',
                // 'RM Action Date'        => '',
                // 'Comment For Rejection' => '',
                // 'Detail Status'         => '',
            ];

            $details = InvoiceDetail::with([
                'item',
                'itemuom',
                'promotion',
                'approver',
            ])
                ->where('header_id', $invoice->id)
                ->where('item_id', $this->itemId)
                ->get();

            foreach ($details as $detail) {
                $rows[] = [
                    // 'Invoice Code'     => '',
                    // 'Currency Name'    => '',
                    // 'Company Name'     => '',
                    // 'Order Code'       => '',
                    // 'Delivery Code'    => '',
                    // 'Warehouse Code'   => '',
                    // 'Warehouse Name'   => '',
                    // 'Route Code'       => '',
                    // 'Route Name'       => '',
                    // 'Customer Code'    => '',
                    // 'Customer Name'    => '',
                    // 'Salesman Code'    => '',
                    // 'Salesman Name'    => '',
                    // 'Invoice Date'     => '',
                    // 'Invoice Time'     => '',
                    // 'Gross Total'      => '',
                    // 'VAT'              => '',
                    // 'Pre VAT'          => '',
                    // 'Net Total'        => '',
                    // 'Promotion Total'  => '',
                    // 'Discount'         => '',
                    // 'Total Amount'     => '',
                    // 'Status'           => '',

                    // 'Item Code'             => (string) ($detail->item->item_code ?? ''),
                    // 'Item Name'             => (string) ($detail->item->item_name ?? ''),
                    // 'UOM Code'              => (string) ($detail->itemuom->uom_code ?? ''),
                    // 'UOM Name'              => (string) ($detail->itemuom->uom_name ?? ''),
                    // 'Quantity'              => (float) $detail->quantity,
                    // 'Item Value'            => (float) $detail->itemvalue,
                    // 'VAT (Detail)'          => (float) $detail->vat,
                    // 'Pre VAT (Detail)'      => (float) $detail->pre_vat,
                    // 'Net Total (Detail)'    => (float) $detail->net_total,
                    // 'Item Total'            => (float) $detail->item_total,
                    // 'Promotion Code'        => (string) ($detail->promotion->promotion_code ?? ''),
                    // 'Parent'                => (string) ($detail->parent ?? ''),
                    // 'Approver Name'         => (string) ($detail->approver->name ?? ''),
                    // 'Approved Date'         => (string) ($detail->approved_date ?? ''),
                    // 'Rejected By'           => '',
                    // 'RM Approver Name'      => '',
                    // 'RM Reject Name'        => '',
                    // 'RM Action Date'        => '',
                    // 'Comment For Rejection' => '',
                    'Detail Status'         => $detail->status == 1 ? 'Active' : 'Inactive',
                ];
            }
            $rows[] = array_fill_keys(array_keys($rows[0]), '');
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Currency Name',
            'Company Name',
            'Order Code',
            'Delivery Code',
            'Warehouse Code',
            'Warehouse Name',
            'Route Code',
            'Route Name',
            'Customer Code',
            'Customer Name',
            'Salesman Code',
            'Salesman Name',
            'Invoice Date',
            'Invoice Time',
            'Gross Total',
            'VAT',
            'Pre VAT',
            'Net Total',
            'Promotion Total',
            'Discount',
            'Total Amount',
            'Status',
            'Item Code',
            'Item Name',
            'UOM Code',
            'UOM Name',
            'Quantity',
            'Item Value',
            'VAT (Detail)',
            'Pre VAT (Detail)',
            'Net Total (Detail)',
            'Item Total',
            'Promotion Code',
            'Parent',
            'Approver Name',
            'Approved Date',
            'Rejected By',
            'RM Approver Name',
            'RM Reject Name',
            'RM Action Date',
            'Comment For Rejection',
            'Detail Status',
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
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}