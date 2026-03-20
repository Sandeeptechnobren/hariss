<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ReturnHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class ReturnHeaderExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct($fromDate, $toDate, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate   ?: $today;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }
  public function collection()
    {
        $rows = [];

        $query = ReturnHeader::with([
            'country',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman',
        ])
            ->when(
                $this->fromDate,
                fn($q) => $q->whereDate('created_at', '>=', $this->fromDate)
            )
            ->when(
                $this->toDate,
                fn($q) => $q->whereDate('created_at', '<=', $this->toDate)
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
        $headers = $query->get();

        foreach ($headers as $header) {

            $rows[] = [
                'Code'      => (string) $header->osa_code,
                'Date' => (string) optional($header->created_at)->format('Y-m-d'),
                // 'Order Code'    => (string) ($header->order->order_code ?? ''),
                'Delivery Code' => (string) ($header->delivery->delivery_code ?? ''),
                'Warehouse' => trim(
                    ($header->warehouse->warehouse_code ?? '') . ' - ' .
                        ($header->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($header->route->route_code ?? '') . ' - ' .
                        ($header->route->route_name ?? '')
                ),

                'Customer' => trim(
                    ($header->customer->osa_code ?? '') . ' - ' .
                        ($header->customer->name ?? '')
                ),

                'Salesman' => trim(
                    ($header->salesman->osa_code ?? '') . ' - ' .
                        ($header->salesman->name ?? '')
                ),

                'VAT'        => (float) $header->vat,
                'Net Amount' => (float) $header->net_amount,
                'Total'      => (float) $header->total,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Date',
            'Delivery Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'VAT',
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
