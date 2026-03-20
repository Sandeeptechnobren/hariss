<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtDeliveryFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $from_date;
    protected $to_date;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function collection()
    {

        $query = HTDeliveryHeader::with([
            'customer',
            'country',
            'salesman',
            'poorder',
            'order'
        ]);

        if ($this->from_date && $this->to_date) {
            $query->whereDate('delivery_date', '>=', $this->from_date)
                ->whereDate('delivery_date', '<=', $this->to_date);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }
        // dd($query);
        $deliveries = $query->get();

        $rows = [];

        foreach ($deliveries as $d) {

            $rows[] = [

                'Delivery Code' => (string) $d->delivery_code,

                'Delivery Date' => $d->delivery_date
                    ? date('Y-m-d', strtotime($d->delivery_date))
                    : null,

                'PurchaseOrder Code' => (string) ($d->poorder->order_code ?? ''),

                'Order Code' => (string) ($d->order->order_code ?? ''),

                'Customer' => trim(
                    ($d->customer->osa_code ?? '') .
                        ' - ' .
                        ($d->customer->business_name ?? '')
                ),

                'Salesman' => trim(
                    ($d->salesman->osa_code ?? '') .
                        ' - ' .
                        ($d->salesman->name ?? '')
                ),

                'VAT' => (float) $d->vat,

                'Net Amount' => (float) $d->net,

                'Excise' => (float) $d->excise,

                'Total' => (float) $d->total,

                'Comment' => (string) $d->comment,

            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Delivery Code',
            'Delivery Date',
            'PurchaseOrder Code',
            'Order Code',
            'Customer',
            'Salesman',
            'VAT',
            'Net Amount',
            'Excise',
            'Total',
            'Comment',
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
            }

        ];
    }
}
