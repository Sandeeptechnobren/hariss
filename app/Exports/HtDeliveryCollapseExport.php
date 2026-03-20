<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTDeliveryHeader;
use App\Models\Hariss_Transaction\Web\HTDeliveryDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtDeliveryCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $customerId;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct(
        $fromDate = null,
        $toDate = null,
        $warehouseIds = [],
        $salesmanIds = []
    ) {
        $this->fromDate = $fromDate ?: now()->toDateString();
        $this->toDate   = $toDate   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }
    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = HTDeliveryHeader::with([
            'customer',
            'salesman',
            'country',
            'poorder',
            'order'
        ]);
        if (!empty($this->fromDate) && !empty($this->toDate)) {
            $query->whereBetween('delivery_date', [$this->fromDate, $this->toDate]);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }
        // dd($query->count());
        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            // Header Row
            $rows[] = [
                'Delivery Code'        => (string) $header->delivery_code,
                'Delivery Date'        => $header->delivery_date
                    ? date('Y-m-d', strtotime($header->delivery_date))
                    : '',
                'PurchaseOrder Code'   => (string) ($header->poorder->order_code ?? ''),
                'Order Code'           => (string) ($header->order->order_code ?? ''),
                'Customer'        =>  trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                'Salesman'        => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                'VAT'                  => (float) $header->vat,
                'Net Amount'           => (float) $header->net,
                'Excise'               => (float) $header->excise,
                'Total'                => (float) $header->total,
                'Comment'              => (string) $header->comment,


                'Item'                 => '',
                'UOM Name'             => '',
                'Item Price'           => '',
                'Quantity'             => '',
                'Net Detail'           => '',
                'Excise Detail'        => '',
                'Detail VAT'           => '',
                'Detail Total'         => '',
            ];

            $rowIndex++;

            $details = HTDeliveryDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'Delivery Code'        => '',
                    'Delivery Date'        => '',
                    'PurchaseOrder Code'   => '',
                    'Order Code'           => '',
                    'Customer'             => '',
                    'Salesman'             => '',
                    'VAT'                  => '',
                    'Net Amount'           => '',
                    'Excise'               => '',
                    'Total'                => '',
                    'Comment'              => '',


                    'Item'                 => trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                    'UOM Name'             => (string) ($detail->uoms->name ?? ''),
                    'Item Price'           => (float) $detail->item_price,
                    'Quantity'             => (float) $detail->quantity,
                    'Net Detail'           => (float) $detail->net,
                    'Excise Detail'        => (float) $detail->excise,
                    'Detail VAT'           => (float) $detail->vat,
                    'Detail Total'         => (float) $detail->total,
                ];

                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }
            if (!empty($detailRowIndexes)) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            $rows[] = array_fill_keys(array_keys($rows[0]), '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return array_keys($this->collection()->first());
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
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
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
