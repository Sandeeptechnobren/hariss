<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtOrderFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $from_date;
    protected $to_date;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds = $salesmanIds;
    }

    public function collection()
    {

        $query = HTOrderHeader::with([
            'customer:id,osa_code,business_name',
            'salesman:id,osa_code,name',
            'warehouse:id,warehouse_code,warehouse_name'
        ]);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('order_date', [$this->from_date, $this->to_date]);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        $orders = $query->get();

        $rows = [];

        foreach ($orders as $h) {

            $rows[] = [

                'Code' => (string) $h->order_code,

                'Order Date' => (string) ($h->order_date?->format('d M Y') ?? ''),
                'Delivery Date' => (string) ($h->delivery_date?->format('d M Y') ?? ''),

                'Customer' => trim(
                    ($h->customer->osa_code ?? '') .
                        ' - ' .
                        ($h->customer->business_name ?? '')
                ),
                'Warehouse' => trim(
                    ($h->warehouse->warehouse_code ?? '') .
                        ' - ' .
                        ($h->warehouse->warehouse_name ?? '')
                ),

                'Salesman' => trim(
                    ($h->salesman->osa_code ?? '') .
                        ' - ' .
                        ($h->salesman->name ?? '')
                ),


                'SAP ID' => (string) $h->sap_id,

                'SAP MSG' => (string) $h->sap_msg,

                'Comment' => (string) ($h->comment ?? ''),

                'VAT' => number_format((float)$h->vat, 2, '.', ','),

                'Excise' => number_format((float)$h->excise, 2, '.', ','),

                'Net Amount' => number_format((float)$h->net_amount, 2, '.', ','),

                'Total' => number_format((float)$h->total, 2, '.', ','),

            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Order Date',
            'Delivery Date',
            'Customer',
            'Warehouse',
            'Salesman',
            'SAP ID',
            'SAP MSG',
            'Comment',
            'VAT',
            'Excise',
            'Net Amount',
            'Total',
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
                        'vertical'   => Alignment::VERTICAL_CENTER,
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
            }

        ];
    }
}
