<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class InvoiceSalesmanExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $salesmanId;

   public function __construct($salesmanId, $fromDate = null, $toDate = null)
    {
        $today = now()->toDateString();

        $this->salesmanId = $salesmanId;

        $this->fromDate = (!empty($fromDate) && strtotime($fromDate))
            ? $fromDate
            : $today;

        $this->toDate = (!empty($toDate) && strtotime($toDate))
            ? $toDate
            : $today;
    }

    // public function query()
    // {
    //     return InvoiceHeader::with([
    //             'delivery',
    //             'warehouse',
    //             'route',
    //             'customer',
    //             'salesman',
    //         ])
    //         if($this->salesmanId)
    //          {
    //            ->where('salesman_id', $this->salesmanId)
                
    //          }
    //          if($this->fromDate)
    //          {
    //                        ->whereDate('invoice_date', '>=', $this->fromDate)

                
    //          }
    //          if($this->toDate)
    //          {
    //                      ->whereDate('invoice_date', '<=', $this->toDate);

                
    //          }
    // }
    public function query()
{
    $query = InvoiceHeader::with([
        'delivery',
        'warehouse',
        'route',
        'customer',
        'salesman',
    ]);
     
    if (!empty($this->salesmanId)) {
        $query->where('salesman_id', $this->salesmanId);
    }

    if (!empty($this->fromDate)) {
        $query->whereDate('invoice_date', '>=', $this->fromDate);
    }

    if (!empty($this->toDate)) {
        $query->whereDate('invoice_date', '<=', $this->toDate);
    }

    return $query;
}

    public function map($header): array
    {
        return [
            $header->invoice_code,

            $header->invoice_date
                ? \Carbon\Carbon::parse($header->invoice_date)->format('d M Y')
                : '',

            $header->invoice_time
                ? \Carbon\Carbon::parse($header->invoice_time)->format('h:i A')
                : '',

            $header->delivery->delivery_code ?? '',

            trim(
                ($header->warehouse->warehouse_code ?? '') . ' - ' .
                ($header->warehouse->warehouse_name ?? '')
            ),

            trim(
                ($header->route->route_code ?? '') . ' - ' .
                ($header->route->route_name ?? '')
            ),

            trim(
                ($header->customer->osa_code ?? '') . ' - ' .
                ($header->customer->name ?? '')
            ),

            trim(
                ($header->salesman->osa_code ?? '') . ' - ' .
                ($header->salesman->name ?? '')
            ),

            number_format($header->vat, 2, '.', ''),
            number_format($header->net_total, 2, '.', ''),
            number_format($header->total_amount, 2, '.', ''),
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Invoice Date',
            'Invoice Time',
            'Delivery Code',
            'Distributor',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Total',
            'Total Amount',
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
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}