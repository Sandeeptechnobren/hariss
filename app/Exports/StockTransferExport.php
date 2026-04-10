<?php

namespace App\Exports;

use App\Models\StockTransferHeader;
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

class StockTransferExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;

    public function __construct($fromDate, $toDate, $warehouseIds = [])
    {
        $today = now()->toDateString();
        $this->fromDate = $fromDate ?: $today;
        $this->toDate   = $toDate ?: $today;
        $this->warehouseIds = $warehouseIds;
    }

    public function query()
    {
        $query = StockTransferHeader::with([
            'sourceWarehouse',
            'destinyWarehouse',
            'details'
        ])
            ->when(
                $this->fromDate,
                fn($q) =>
                $q->whereDate('transfer_date', '>=', $this->fromDate)
            )
            ->when(
                $this->toDate,
                fn($q) =>
                $q->whereDate('transfer_date', '<=', $this->toDate)
            )
            ->whereIn('source_warehouse', $this->warehouseIds)
            ->whereNull('deleted_at');
        // dd($query->count());
        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        return $query;
    }

    public function map($header): array
    {
        return [
            $header->osa_code,

            $header->transfer_date
                ? \Carbon\Carbon::parse($header->transfer_date)->format('d M Y')
                : '',

            $header->transfer_date
                ? \Carbon\Carbon::parse($header->transfer_date)->format('h:i A')
                : '',

            trim(
                ($header->sourceWarehouse->warehouse_code ?? '') . ' - ' .
                    ($header->sourceWarehouse->warehouse_name ?? '')
            ),

            trim(
                ($header->destinyWarehouse->warehouse_code ?? '') . ' - ' .
                    ($header->destinyWarehouse->warehouse_name ?? '')
            ),

            // 👉 Total Items Count
            $header->details->count(),

            // 👉 Total Quantity
            number_format(
                $header->details->sum('transfer_qty'),
                2,
                '.',
                ''
            ),

            // $header->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Transfer Code',
            'Date',
            'Time',
            'Source Warehouse',
            'Destination Warehouse',
            'Total Items',
            'Total Quantity',
            // 'Status',
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
