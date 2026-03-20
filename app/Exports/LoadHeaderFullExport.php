<?php

namespace App\Exports;

use App\Models\Agent_Transaction\LoadHeader;
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

class LoadHeaderFullExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected array $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $routeIds = [], $salesmanIds = [])
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

        $query = LoadHeader::with([
            'warehouse',
            'route',
            'salesman',
            'salesmantype',
            'projecttype'
        ])
            ->when(
                $this->fromDate && $this->toDate,
                fn($q) =>
                $q->whereBetween('created_at', [
                    $this->fromDate . ' 00:00:00',
                    $this->toDate . ' 23:59:59'
                ])
            )

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
            $loads = $query->get();

        foreach ($loads as $load) {

            $rows[] = [
                'Load No' => (string) $load->osa_code,
                'Load Date' => optional($load->created_at)->format('d-m-Y'),
                'Accept Date' => $load->accept_time
                    ? Carbon::parse($load->accept_time)->format('d-m-Y')
                    : '',
                'Warehouse' => trim(
                    ($load->warehouse->warehouse_code ?? '') . ' - ' .
                        ($load->warehouse->warehouse_name ?? '')
                ),

                'Route' => trim(
                    ($load->route->route_code ?? '') . ' - ' .
                        ($load->route->route_name ?? '')
                ),

                'Salesman' => trim(
                    ($load->salesman->osa_code ?? '') . ' - ' .
                        ($load->salesman->name ?? '')
                ),

                'Salesman Type' => (string) ($load->salesmantype->salesman_type_name ?? ''),
                'Project Type'  => (string) ($load->projecttype->name ?? ''),

                'Status' => $load->is_confirmed == 1 ? 'SalesTeam Accepted' : 'Waiting For Accept',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Load No',
            'Load Date',
            'Accept Date',
            'Warehouse',
            'Route',
            'Salesman',
            'Salesman Type',
            'Project Type',
            'Status',
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
