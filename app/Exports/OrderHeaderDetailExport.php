<?php

namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\OrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OrderHeaderDetailExport implements FromCollection, WithHeadings, ShouldAutoSize,WithEvents
{
        protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
          $rows = [];
        $query = OrderHeader::with(['warehouse', 'customer']);
        if ($this->uuid) {
            $query->where('uuid', $this->uuid); 
        }
        $headers = $query->get();
        foreach ($headers as $header) {
            $rows[] = [
                'Order Code'     => (string) $header->order_code,
                'Warehouse Name' => (string) ($header->warehouse->warehouse_name ?? ''),
                'Customer Name'  => (string) ($header->customer->name ?? ''),
                'Delivery Date'  => (string) ($header->delivery_date?->format('Y-m-d') ?? ''),
                'Comment'        => (string) ($header->comment ?? ''),
                'Status'         => (string) ($header->status == 1 ? 'Active' : 'Inactive'),

                'Item Name'      => '',
                'UOM Name'       => '',
                'Item Price'     => '',
                'Quantity'       => '',
                'VAT'            => '',
                'Discount'       => '',
                'Gross Total'    => '',
                'Net Total'      => '',
                'Total'          => '',
                'Detail Status'  => '',
            ];

            $details = OrderDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->get();

            foreach ($details as $detail) {
                $rows[] = [
                    'Order Code'     => '',
                    'Warehouse Name' => '',
                    'Customer Name'  => '',
                    'Delivery Date'  => '',
                    'Comment'        => '',
                    'Status'         => '',

                    'Item Name'      => (string) ($detail->item->name ?? ''),
                    'UOM Name'       => (string) ($detail->uom->name ?? ''),
                    'Item Price'     => (float) $detail->item_price,
                    'Quantity'       => (float) $detail->quantity,
                    'VAT'            => (float) $detail->vat,
                    'Discount'       => (float) $detail->discount,
                    'Gross Total'    => (float) $detail->gross_total,
                    'Net Total'      => (float) $detail->net_total,
                    'Total'          => (float) $detail->total,
                    'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
                ];
            }

            $rows[] = [
                'Order Code'     => '',
                'Warehouse Name' => '',
                'Customer Name'  => '',
                'Delivery Date'  => '',
                'Comment'        => '',
                'Status'         => '',
                'Item Name'      => '',
                'UOM Name'       => '',
                'Item Price'     => '',
                'Quantity'       => '',
                'VAT'            => '',
                'Discount'       => '',
                'Gross Total'    => '',
                'Net Total'      => '',
                'Total'          => '',
                'Detail Status'  => '',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Warehouse Name',
            'Customer Name',
            'Delivery Date',
            'Comment',
            'Status',
            'Item Name',
            'UOM Name',
            'Item Price',
            'Quantity',
            'VAT',
            'Discount',
            'Gross Total',
            'Net Total',
            'Total',
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
