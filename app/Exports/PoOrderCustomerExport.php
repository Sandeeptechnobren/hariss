<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PoOrderCustomerExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithChunkReading
{
    protected $from_date;
    protected $to_date;
    protected $customer_id;
    protected $warehouseIds;
    protected $routeIds;
    protected $salesmanIds;

    public function __construct(
        $customer_id  = null,
        $from_date    = null,
        $to_date      = null,
        $warehouseIds = [],
        $routeIds     = [],
        $salesmanIds  = []
    ) {
        $this->customer_id  = $customer_id;

        $this->from_date = $from_date;
        $this->to_date   = $to_date;

        $this->warehouseIds = $warehouseIds;
        $this->routeIds     = $routeIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function query()
    {
        $query = PoOrderHeader::query()
            ->with([
                'customer:id,osa_code,business_name',
                'salesman:id,osa_code,name',
                'warehouse:id,warehouse_code,warehouse_name',
            ])
            ->select([
                'id',
                'order_code',
                'order_date',
                'delivery_date',
                'sap_id',
                'sap_msg',
                'customer_id',
                'salesman_id',
                'comment',
                'vat',
                'net',
                'total',
                'status',
                'warehouse_id',
            ]);

        if (!empty($this->customer_id)) {
            $query->where('customer_id', $this->customer_id);
        }

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('created_at', [
                $this->from_date,
                $this->to_date
            ]);
        } else {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

        return $query;
    }

    public function map($h): array
    {
        $distributor = trim(
            ($h->warehouse->warehouse_code ?? '') . ' - ' .
            ($h->warehouse->warehouse_name ?? '')
        );

        $distributor = ($distributor === ' - ' || $distributor === '-') ? '-' : $distributor;

        $statusLabel = match ((int) $h->status) {
            1 => 'Order Created',
            2 => 'Delivery Created',
            3 => 'Delivered',
            default => '-',
        };

        return [
            (string) ($h->sap_id ?? '-'),
            (string) $h->order_code,
            optional($h->order_date)->format('d M Y'),
            $distributor,
            trim(($h->customer->osa_code ?? '') . ' - ' . ($h->customer->business_name ?? '')),
            trim(($h->salesman->osa_code ?? '') . ' - ' . ($h->salesman->name ?? '')),
            optional($h->delivery_date)->format('d M Y'),
            (string) ($h->comment ?? '-'),
            number_format((float) $h->total, 2, '.', ','),
            $statusLabel,
        ];
    }

    public function headings(): array
    {
        return [
            'SAP',
            'Order Number',
            'Order Date',
            'Distributor',
            'Customer',
            'Sales Team',
            'Delivery Date',
            'Comment',
            'Amount',
            'Status',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

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

                if ($lastRow > 1) {
                    $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 10,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'DDDDDD'],
                            ],
                        ],
                    ]);
                }

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->freezePane('A2');
            },
        ];
    }
}
