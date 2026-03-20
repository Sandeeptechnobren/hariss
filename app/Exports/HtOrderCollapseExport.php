<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTOrderHeader;
use App\Models\Hariss_Transaction\Web\HTOrderDetail;
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

class HtOrderCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{

    protected $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->fromDate = $fromDate ?: now()->toDateString();
        $this->toDate   = $toDate   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function collection()
    {

        $rows = [];
        $rowIndex = 2;

        $query = HTOrderHeader::with([
            'customer:id,osa_code,business_name',
            'salesman:id,osa_code,name'
        ]);
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);

        $headers = $query->get();

        $details = HTOrderDetail::with(['item', 'uoms'])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;

            $rows[] = [

                'Order Code' => (string)$header->order_code,

                'Order Date' => (string)($header->order_date?->format('Y-m-d') ?? ''),

                'Customer' => trim(
                    ($header->customer->osa_code ?? '') .
                        ' - ' .
                        ($header->customer->business_name ?? '')
                ),

                'Salesman' => trim(
                    ($header->salesman->osa_code ?? '') .
                        ' - ' .
                        ($header->salesman->name ?? '')
                ),

                'Delivery Date' => (string)($header->delivery_date?->format('Y-m-d') ?? ''),

                'SAP ID' => (string)$header->sap_id,

                'SAP MSG' => (string)$header->sap_msg,

                'Comment' => (string)($header->comment ?? ''),

                'Net Amount' => number_format((float)$header->net_amount, 2, '.', ','),

                'Excise' => number_format((float)$header->excise, 2, '.', ','),

                'VAT' => number_format((float)$header->vat, 2, '.', ','),

                'Total' => number_format((float)$header->total, 2, '.', ','),

                'Item' => '',
                'UOM Name' => '',
                'Item Price' => '',
                'Quantity' => '',
                'Net' => '',
                'Excise Detail' => '',
                'Detail VAT' => '',
                'Detail Total' => '',
            ];

            $rowIndex++;

            $detailRows = [];

            foreach ($details[$header->id] ?? [] as $detail) {

                $rows[] = [

                    'Order Code' => '',
                    'Order Date' => '',
                    'Customer' => '',
                    'Salesman' => '',
                    'Delivery Date' => '',
                    'SAP ID' => '',
                    'SAP MSG' => '',
                    'Comment' => '',
                    'Net Amount' => '',
                    'Excise' => '',
                    'VAT' => '',
                    'Total' => '',

                    'Item' => trim(
                        ($detail->item->erp_code ?? '') .
                            ' - ' .
                            ($detail->item->name ?? '')
                    ),

                    'UOM Name' => (string)($detail->uoms->name ?? ''),

                    'Item Price' => number_format((float)$detail->item_price, 2, '.', ','),

                    'Quantity' => (float)$detail->quantity,

                    'Net' => number_format((float)$detail->net, 2, '.', ','),

                    'Excise Detail' => number_format((float)$detail->excise, 2, '.', ','),

                    'Detail VAT' => number_format((float)$detail->vat, 2, '.', ','),

                    'Detail Total' => number_format((float)$detail->total, 2, '.', ','),

                ];

                $detailRows[] = $rowIndex;
                $rowIndex++;
            }

            if (!empty($detailRows)) {
                $this->groupIndexes[] = [
                    'start' => min($detailRows),
                    'end' => max($detailRows),
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
            'Order Code',
            'Order Date',
            'Customer',
            'Salesman',
            'Delivery Date',
            'SAP ID',
            'SAP MSG',
            'Comment',
            'Net Amount',
            'Excise',
            'VAT',
            'Total',

            'Item',
            'UOM Name',
            'Item Price',
            'Quantity',
            'Net',
            'Excise Detail',
            'Detail VAT',
            'Detail Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);

        $sheet->getStyle('A1:S1')
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
                        'vertical' => Alignment::VERTICAL_CENTER,
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
