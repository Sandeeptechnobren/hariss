<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\Agent_Transaction\OrderDetail;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesmanPoExport implements FromArray, WithEvents
{
    protected $headers;

    public function __construct($headers)
    {
        $this->headers = $headers;
    }

 public function array(): array
{
    $rows = [];
    $rows[] = [
        'Warehouse',
        'Customer',
        'Delivery Date',
        'Comment',
        'Order Code',
        'Status',
        'Currency',
        'Company',
        'Route',
        'Salesman',
        'Gross Total',
        'Pre VAT',
        'VAT',
        'Discount',
        'Net',
        'Total',
        'Order Flag',
        'Excise',

        'Item',
        'UOM',
        'Discount',
        'Item Price',
        'Quantity',
        'VAT',
        'Discount',
        'Gross Total',
        'Net',
        'Excise',
        'Pre VAT',
        'Total'
    ];
    $orderFlagMap = [
        1 => 'Created Order',
        2 => 'Delivery Created',
        3 => 'Delivered',
    ];
    foreach ($this->headers as $header) {
        $rows[] = array_merge(
            [
                $header->warehouse?->warehouse_name,
                $header->customer?->business_name,
                $header->delivery_date,
                $header->comment,
                $header->order_code,
                $header->status == 1 ? 'Active' : 'Inactive',
                $header->currency,
                $header->company?->company_name,
                $header->route?->route_name,
                $header->salesman?->name,
                $header->gross_total,
                $header->vat,
                $header->pre_vat,
                $header->excise,
                $header->discount,
                $header->net,
                $header->total,
                $orderFlagMap[$header->order_flag] ?? 'Unknown', 
            
            ],
            array_fill(0, 10, '')
        );
        foreach ($header->details as $detail) {
            $rows[] = array_merge(
                array_fill(0, 18, ''),
                [
                    $detail->item?->name,
                    $detail->uom?->name,
                    // $detail->discounts?->discount_name,
                    $detail->item_price,
                    $detail->quantity,
                    $detail->vat,
                    $detail->discount,
                    $detail->gross_total,
                    $detail->net,
                    $detail->excise,
                    $detail->pre_vat,
                    $detail->total
                ]
            );
        }
    }
    return $rows;
}
public function registerEvents(): array
{
    return [
        AfterSheet::class => function ($event) {
            $sheet = $event->sheet->getDelegate();
            $lastColumn = 'AE';
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'F5F5F5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '993442'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'], 
                    ],
                ],
            ]);
            $row = 2; 
            foreach ($this->headers as $header) {
                $detailCount = $header->details->count();

                if ($detailCount > 0) {
                    $start = $row + 1;
                    $end   = $row + $detailCount;

                    for ($i = $start; $i <= $end; $i++) {
                        $sheet->getRowDimension($i)
                              ->setOutlineLevel(1)
                              ->setVisible(false)
                              ->setCollapsed(true);
                    }

                    $row = $row + 1 + $detailCount;
                } else {
                    $row++;
                }
            }

            $sheet->setShowSummaryBelow(true);
        }
    ];
}
}
