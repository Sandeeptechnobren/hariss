<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use App\Models\Agent_Transaction\ExchangeDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExchangeAllExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
    {
        $rows = [];
        $query = ExchangeHeader::with(['warehouse', 'customer']);

        if ($this->uuid) {
            $query->where('uuid', $this->uuid);
        }

        $headers = $query->get();

        foreach ($headers as $header) {
            $rows[] = [
                'Exchange Code'  => $header->exchange_code,
                'Warehouse Code' => $header->warehouse->warehouse_code ?? '',
                'Warehouse Name' => $header->warehouse->warehouse_name ?? '',
                'Customer Code'  => $header->customer->osa_code ?? '',
                'Customer Name'  => $header->customer->name ?? '',
                'Comment'        => $header->comment,
                'Status'         => $header->status == 1 ? 'Active' : 'Inactive',
                'Section'        => '',
                'Item Code'      => '',
                'Item Name'      => '',
                'UOM Name'       => '',
                'Item Price'     => '',
                'Item Quantity'  => '',
                'Total'          => '',
                'Detail Status'  => '',
            ];

            $collectDetails = ExchangeDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->where('type', 'collect')
                ->get();

            if ($collectDetails->count()) {
                $rows[] = [
                    'Exchange Code'  => '',
                    'Warehouse Code' => '',
                    'Warehouse Name' => '',
                    'Customer Code'  => '',
                    'Customer Name'  => '',
                    'Comment'        => '',
                    'Status'         => '',
                    'Section'        => 'Collect',
                    'Item Code'      => '',
                    'Item Name'      => '',
                    'UOM Name'       => '',
                    'Item Price'     => '',
                    'Item Quantity'  => '',
                    'Total'          => '',
                    'Detail Status'  => '',
                ];

                foreach ($collectDetails as $detail) {
                    $rows[] = [
                        'Exchange Code'  => '',
                        'Warehouse Code' => '',
                        'Warehouse Name' => '',
                        'Customer Code'  => '',
                        'Customer Name'  => '',
                        'Comment'        => '',
                        'Status'         => '',
                        'Section'        => '',
                        'Item Code'      => '→ ' . ($detail->item->code ?? ''),
                        'Item Name'      => $detail->item->name ?? '',
                        'UOM Name'       => $detail->uom->name ?? '',
                        'Item Price'     => (float) $detail->item_price,
                        'Item Quantity'  => (float) $detail->item_quantity,
                        'Total'          => (float) $detail->total,
                        'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
                    ];
                }
            }

            $returnDetails = ExchangeDetail::with(['item', 'uom'])
                ->where('header_id', $header->id)
                ->where('type', 'return') 
                ->get();

            if ($returnDetails->count()) {
                $rows[] = [
                    'Exchange Code'  => '',
                    'Warehouse Code' => '',
                    'Warehouse Name' => '',
                    'Customer Code'  => '',
                    'Customer Name'  => '',
                    'Comment'        => '',
                    'Status'         => '',
                    'Section'        => 'Return',
                    'Item Code'      => '',
                    'Item Name'      => '',
                    'UOM Name'       => '',
                    'Item Price'     => '',
                    'Item Quantity'  => '',
                    'Total'          => '',
                    'Detail Status'  => '',
                ];

                foreach ($returnDetails as $detail) {
                    $rows[] = [
                        'Exchange Code'  => '',
                        'Warehouse Code' => '',
                        'Warehouse Name' => '',
                        'Customer Code'  => '',
                        'Customer Name'  => '',
                        'Comment'        => '',
                        'Status'         => '',
                        'Section'        => '',
                        'Item Code'      => '→ ' . ($detail->item->code ?? ''),
                        'Item Name'      => $detail->item->name ?? '',
                        'UOM Name'       => $detail->uom->name ?? '',
                        'Item Price'     => (float) $detail->item_price,
                        'Item Quantity'  => (float) $detail->item_quantity,
                        'Total'          => (float) $detail->total,
                        'Detail Status'  => $detail->status == 1 ? 'Active' : 'Inactive',
                    ];
                }
            }
            $rows[] = array_fill_keys(array_keys($rows[0]), '');
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Exchange Code',
            'Warehouse Code',
            'Warehouse Name',
            'Customer Code',
            'Customer Name',
            'Comment',
            'Status',
            'Section',
            'Item Code',
            'Item Name',
            'UOM Name',
            'Item Price',
            'Item Quantity',
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
                    'font' => ['bold' => true, 'color' => ['rgb' => 'F5F5F5']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getStyle('H')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
