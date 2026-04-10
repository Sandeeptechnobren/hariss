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

class PoOrderExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithChunkReading
{

    protected $from_date;
    protected $to_date;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds = $salesmanIds;
    }

    /**
     * Export Query
     */
    public function query()
    {

        $query = PoOrderHeader::query()
            ->with([
                'customer:id,osa_code,business_name',
                'salesman:id,osa_code,name',
                'warehouse:id,warehouse_code,warehouse_name'
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
                'warehouse_id'
            ]);

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('order_date', [$this->from_date, $this->to_date]);
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

    /**
     * Row Mapping
     */
    public function map($h): array
    {
        return [
            (string) $h->order_code,

            optional($h->order_date)->format('d M Y'),

            optional($h->delivery_date)->format('d M Y'),

            (string) $h->sap_id,

            (string) $h->sap_msg,

            trim(
                ($h->customer->osa_code ?? '') . '-' .
                    ($h->customer->business_name ?? '')
            ),

            trim(
                ($h->salesman->osa_code ?? '') . '-' .
                    ($h->salesman->name ?? '')
            ),

            (string) $h->comment,
            // $h->sap_msg,
            number_format((float) $h->vat, 2, '.', ','),
            number_format((float) $h->net, 2, '.', ','),
            number_format((float) $h->total, 2, '.', ','),
        ];
    }

    /**
     * Excel Headings
     */
    public function headings(): array
    {
        return [
            'Code',
            'Order Date',
            'Delivery Date',
            'SAP ID',
            'SAP MSG',
            'Customer',
            'Salesman',
            'Comment',
            // 'Status',
            'VAT',
            'Net Amount',
            'Total',
        ];
    }

    /**
     * Chunk size for memory optimization
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Excel Header Styling
     */
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
            }

        ];
    }
}
