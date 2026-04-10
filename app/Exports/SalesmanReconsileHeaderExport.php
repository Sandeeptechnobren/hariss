<?php

namespace App\Exports;

use App\Models\Agent_Transaction\SalesmanReconsileHeader;
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
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesmanReconsileHeaderExport implements FromQuery, WithMapping, WithHeadings, WithEvents, ShouldAutoSize
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($fromDate, $toDate, $warehouseIds = [], $salesmanIds = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds = $salesmanIds;
    }

    public function query()
    {
        return SalesmanReconsileHeader::with([
            'warehouse',
            'salesman'
        ])
            ->withCount('details')
            ->when(
                $this->fromDate,
                fn($q) =>
                $q->whereDate('reconsile_date', '>=', $this->fromDate)
            )
            ->when(
                $this->toDate,
                fn($q) =>
                $q->whereDate('reconsile_date', '<=', $this->toDate)
            )
            ->when(
                !empty($this->warehouseIds),
                fn($q) =>
                $q->whereIn('warehouse_id', $this->warehouseIds)
            )
            ->when(
                !empty($this->salesmanIds),
                fn($q) =>
                $q->whereIn('salesman_id', $this->salesmanIds)
            )
            ->whereNull('deleted_at');
    }

    public function map($header): array
    {
        return [
            // $header->uuid,

            $header->reconsile_date
                ? Carbon::parse($header->reconsile_date)->format('d M Y')
                : '',

            trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),

            trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),

            number_format($header->grand_total_amount, 2, '.', ''),
            number_format($header->cash_amount, 2, '.', ''),
            number_format($header->credit_amount, 2, '.', ''),
            $header->details_count, // ✅ ADD THIS
        ];
    }

    public function headings(): array
    {
        return [
            // 'UUID',
            'Date',
            'Warehouse',
            'Salesman',
            'Grand Total',
            'Cash Amount',
            'Credit Amount',
            'Total Item',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
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
            },
        ];
    }
}
