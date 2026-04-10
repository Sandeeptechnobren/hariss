<?php

namespace App\Exports;

use App\Models\Agent_Transaction\UnloadHeader;
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
use App\Services\V1\Agent_Transaction\UnloadHeaderService;

class UnloadHeaderFullExport implements
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

        $query = UnloadHeader::with(['warehouse', 'route', 'salesman', 'details']);


        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->routeIds)) {
            $query->whereIn('route_id', $this->routeIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        // ✅ DATE FILTER (IMPORTANT — match global column)
        if (!empty($this->fromDate)) {
            $query->whereDate('unload_date', '>=', $this->fromDate);
        }

        if (!empty($this->toDate)) {
            $query->whereDate('unload_date', '<=', $this->toDate);
        }

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        $unloads = $query->get();

        foreach ($unloads as $unload) {

            $rows[] = [
                // 'OSA Code'    => (string) ($unload->osa_code ?? ''),
                'Unload No'   => (string) ($unload->unload_no ?? ''),
                'Unload Date' => $unload->unload_date
                    ? \Carbon\Carbon::parse($unload->unload_date)->format('d M Y')
                    : '',

                'Unload Time' => $unload->unload_time
                    ? \Carbon\Carbon::parse($unload->unload_time)->format('h:i A')
                    : '',

                'Load Date' => $unload->load_date
                    ? \Carbon\Carbon::parse($unload->load_date)->format('d M Y')
                    : '',
                'Warehouse' => trim(
                    (optional($unload->warehouse)->warehouse_code ?? '') . ' - ' .
                        (optional($unload->warehouse)->warehouse_name ?? '')
                ),

                'Route' => trim(
                    (optional($unload->route)->route_code ?? '') . ' - ' .
                        (optional($unload->route)->route_name ?? '')
                ),

                'Salesman' => trim(
                    (optional($unload->salesman)->osa_code ?? '') . ' - ' .
                        (optional($unload->salesman)->name ?? '')
                ),

                // 'Load Date' => (string) ($unload->load_date ?? ''),
                'Total Item' => $unload->details ? $unload->details->count() : 0,
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            // 'OSA Code',
            'Unload No',
            'Unload Date',
            'Unload Time',
            'Load Date',
            'Distributors',
            'Route',
            'Salesman',
            'Total Item',
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
