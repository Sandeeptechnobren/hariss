<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ExchangeHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class ExchangeHeaderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from;
    protected $to;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct($from = null, $to = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
    {
        $today = now()->toDateString();
        $this->from = $from ?: $today;
        $this->to   = $to   ?: $today;
        // $this->from = $from;
        // $this->to   = $to;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds = $routeIds;
        $this->salesmanIds = $salesmanIds;
    }

    public function collection()
    {
        $rows = [];

        $query = ExchangeHeader::with([
            'warehouse',
            'route',
            'customer',
            'salesman',
        ])
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('created_at', '<=', $this->to))

            // 🔥 ADDED FILTERS (no change to existing logic)
            ->when(
                !empty($this->salesmanIds),
                fn($q) =>
                $q->whereIn('salesman_id', $this->salesmanIds)
            )

            ->when(
                empty($this->salesmanIds) && !empty($this->routeIds),
                fn($q) =>
                $q->whereIn('route_id', $this->routeIds)
            )

            ->when(
                empty($this->salesmanIds) && empty($this->routeIds) && !empty($this->warehouseIds),
                fn($q) =>
                $q->whereIn('warehouse_id', $this->warehouseIds)
            );

            $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
            $headers = $query->get();

        foreach ($headers as $header) {
            $rows[] = [
                'Exchange'   => (string) $header->exchange_code,
                'Date' => $header->created_at
                    ? Carbon::parse($header->created_at)->format('d M Y')
                    : '',
                'Warehouse' => trim(
                    ($header->warehouse->warehouse_code ?? '') . '-' .
                        ($header->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($header->route->route_code ?? '') . '-' .
                        ($header->route->route_name ?? '')
                ),

                'Customer' => trim(
                    ($header->customer->osa_code ?? '') . '-' .
                        ($header->customer->name ?? '')
                ),

                'Salesman' => trim(
                    ($header->salesman->osa_code ?? '') . '-' .
                        ($header->salesman->name ?? '')
                ),

                'Gross Total'     => (float) $header->gross_total,
                'VAT'             => (float) $header->vat,
                'Net Amount'      => (float) $header->net_amount,
                'Total'           => (float) $header->total,
                'Discount'        => (float) $header->discount,
                // 'Status'          => $header->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Exchange',
            'Date',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'Gross Total',
            'VAT',
            'Net Amount',
            'Total',
            'Discount',
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
