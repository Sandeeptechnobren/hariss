<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use App\Models\Hariss_Transaction\Web\HTInvoiceDetail;
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

class HtInvoiceCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents, WithStyles
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

        $query = HTInvoiceHeader::with([
            'customer',
            'salesman',
            'company',
            'delivery',
            'poorder',
            'order',
            'warehouse'
        ]);

        if (!empty($this->fromDate) && !empty($this->toDate)) {
            $query->whereBetween('invoice_date', [$this->fromDate, $this->toDate]);
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
            $rows[] = [
                'Invoice Code'        => (string) $header->invoice_code,
                'Invoice Date'        => optional($header->invoice_date)->format('Y-m-d'),
                // 'PurchaseOrder Code'  => (string) ($header->poorder->order_code ?? ''),
                // 'Order Code'          => (string) ($header->order->order_code ?? ''),
                'Customer'       => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Salesman'       => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                'Warehouse'      => trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                'Order Number'        => (string) $header->order_number,
                'Delivery Number'     => (string) $header->delivery_number,
                // 'Delivery Code'       => (string) ($header->delivery->delivery_code ?? ''),
                'Net'                 => (float) $header->net,
                'VAT'                 => (float) $header->vat,
                'Excise'              => (float) $header->excise,
                'Total'               => (float) $header->total,

                'Item'                => '',
                'UOM Name'            => '',
                'Quantity'            => '',
                'Item Price'          => '',
                'Discount'            => '',
                'Net Detail'          => '',
                'VAT Detail'          => '',
                'Total Detail'        => '',
                'Batch Number'        => '',
            ];

            $rowIndex++;
            $details = HTInvoiceDetail::with(['item', 'uoms'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {
                $rows[] = [
                    'Invoice Code'        => '',
                    'Invoice Date'        => '',
                    'Invoice Time'        => '',
                    // 'PurchaseOrder Code'  => '',
                    // 'Order Code'          => '',
                    'Customer'       => '',
                    'Salesman'       => '',
                    'Warehouse'      => '',
                    'Order Number'        => '',
                    'Delivery Number'     => '',
                    // 'Delivery Code'       => '',
                    'Net'                 => '',
                    'VAT'                 => '',
                    'Excise'              => '',
                    'Total'               => '',

                    'Item'                => trim(($header->item->erp_code ?? '') . ' - ' . ($header->item->name ?? '')),
                    'UOM Name'            => (string) ($detail->uoms->name ?? ''),
                    'Quantity'            => (float) $detail->quantity,
                    'Item Price'          => (float) $detail->item_price,
                    'Discount'            => (float) $detail->discount,
                    'Net Detail'          => (float) $detail->net,
                    'VAT Detail'          => (float) $detail->vat,
                    'Total Detail'        => (float) $detail->total,
                    'Batch Number'        => (string) $detail->batch_number,
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
        return [
            'Invoice Code',
            'Invoice Date',
            'Invoice Time',
            // 'PurchaseOrder Code',
            // 'Order Code',
            'Customer',
            'Salesman',
            'Warehouse',
            'Order Number',
            'Delivery Number',
            // 'Delivery Code',
            'Net',
            'VAT',
            'Excise',
            'Total',
            'Item',
            'UOM Name',
            'Quantity',
            'Item Price',
            'Discount',
            'Net Detail',
            'VAT Detail',
            'Total Detail',
            'Batch Number',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
            }
        ];
    }
}
