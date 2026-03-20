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
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class InvoiceHeaderExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;
    public function __construct($fromDate, $toDate, $warehouseIds, $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate   ?: $today;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds     = $routeIds;
        $this->salesmanIds  = $salesmanIds;
    }


    public function query()
    {
        $query = InvoiceHeader::with([
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
        ])
        ->when(
            $this->fromDate,
            fn($q) => $q->whereDate('invoice_date', '>=', $this->fromDate)
        )
        ->when(
            $this->toDate,
            fn($q) => $q->whereDate('invoice_date', '<=', $this->toDate)
        )
        ->when(
            !empty($this->warehouseIds),
            fn($q) => $q->whereIn('warehouse_id', $this->warehouseIds)
        )
        ->when(
            !empty($this->routeIds),
            fn($q) => $q->whereIn('route_id', $this->routeIds)
        )
        ->when(
            !empty($this->salesmanIds),
            fn($q) => $q->whereIn('salesman_id', $this->salesmanIds)
        );

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        return $query;
    }

    public function map($header): array
    {
        return [
            $header->invoice_code,
            $header->invoice_date
                ? \Carbon\Carbon::parse($header->invoice_date)->format('d M Y')
                : '',

            // ⏰ Formatted Time
            $header->invoice_time
                ? \Carbon\Carbon::parse($header->invoice_time)->format('h:i A')
                : '',
            // $header->currency_name,
            // $header->order->order_code ?? '',
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


            (float) $header->vat,
            (float) $header->net_total,
            (float) $header->gross_total,
            (float) $header->discount,
            (float) $header->total_amount,
            // $header->status_flag,
        ];
    }

    public function headings(): array
    {
        return [
            'Code',
            'Date',
            'Time',
            // 'Currency Name',
            // 'Order Code',
            'Delivery Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
            'Net Total',
            'Gross Total',
            'Discount',
            'Total Amount',
            // 'Status',
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
                        'bold'  => true,
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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
