<?php

namespace App\Exports;

use App\Models\Agent_Transaction\OrderHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class OrderHeaderFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
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
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }
    public function collection()
    {
        $rows = [];

        $statusMap = [
            1 => 'Order Created',
            2 => 'Delivery Created',
            3 => 'Completed',
        ];

            $query = OrderHeader::with(['warehouse', 'customer', 'salesman', 'route']);
            $query
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

            $orders = $query->get();

        foreach ($orders as $order) {

            $rows[] = [
                'Order Code' => (string) $order->order_code,
                'Order Date' => (string) optional($order->created_at)->format('Y-m-d'),
                'Warehouse' => trim(
                    ($order->warehouse->warehouse_code ?? '') . ' - ' .
                        ($order->warehouse->warehouse_name ?? '')
                ),

                'Customer' => trim(
                    ($order->customer->osa_code ?? '') . ' - ' .
                        ($order->customer->name ?? '')
                ),

                'Salesman' => trim(
                    ($order->salesman->osa_code ?? '') . ' - ' .
                        ($order->salesman->name ?? '')
                ),

                'Route' => trim(
                    ($order->route->route_code ?? '') . ' - ' .
                        ($order->route->route_name ?? '')
                ),

                'Delivery Date' => (string) optional($order->delivery_date)->format('Y-m-d'),
                'Comment'       => (string) ($order->comment ?? ''),
                'Vat'           => (float) $order->vat,
                'Net'           => (float) $order->net_amount,
                'Total'         => (float) $order->total,
                'Status'        => $statusMap[$order->order_flag] ?? 'Unknown',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Order Date',
            'Warehouse',
            'Customer',
            'Salesman',
            'Route',
            'Delivery Date',
            'Comment',
            'Vat',
            'Net',
            'Total',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
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
                        'color' => ['rgb' => 'FFFFFF'],
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

                // Optional header height
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
