<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
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

class ItemPoOrderCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groups = [];
    protected int $rowIndex = 2;

    protected $fromDate;
    protected $toDate;
    protected $customerId;
    protected $itemId; 

    public function __construct($fromDate = null, $toDate = null, $customerId = null, $itemId = null)
    {
        $this->fromDate   = $fromDate;
        $this->toDate     = $toDate;
        $this->customerId = $customerId;
        $this->itemId     = $itemId; 
    }

    public function collection()
    {
        $rows = [];

        $query = PoOrderHeader::with(['customer', 'salesman']);

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);
        }

        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
        }
        if (!empty($this->itemId)) {
            $query->whereHas('details', function ($q) {
                $q->where('item_id', $this->itemId);
            });
        }

        foreach ($query->get() as $header) {

            $rows[] = [
                'Order Code'     => $header->order_code,
                'Order Date'     => optional($header->order_date)->format('Y-m-d'),
                'Delivery Date'  => optional($header->delivery_date)->format('Y-m-d'),
                'Customer'       => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Salesman'       => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),

                'Item'           => '',
                'UOM Name'       => '',
                'Item Price'     => '',
                'Quantity'       => '',
                'Net'            => '',
                'Excise Detail'  => '',
                'Detail VAT'     => '',
                'Detail Total'   => '',
            ];

            $this->rowIndex++;
            $detailsQuery = PoOrderDetail::with(['item', 'uom'])
                ->where('header_id', $header->id);

            if (!empty($this->itemId)) {
                $detailsQuery->where('item_id', $this->itemId);
            }

            $details = $detailsQuery->get();

            $detailRows = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'Order Code'     => '',
                    'Order Date'     => '',
                    'Delivery Date'  => '',
                    'Customer'       => '',
                    'Salesman'       => '',

                    'Item'           => trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    'UOM Name'       => $detail->uom->name ?? '',
                    'Item Price'     => $detail->item_price,
                    'Quantity'       => $detail->quantity,
                    'Net'            => $detail->net,
                    'Excise Detail'  => $detail->excise,
                    'Detail VAT'     => $detail->vat,
                    'Detail Total'   => $detail->total,
                ];

                $detailRows[] = $this->rowIndex;
                $this->rowIndex++;
            }

            if (!empty($detailRows)) {
                $this->groups[] = [
                    'start' => min($detailRows),
                    'end'   => max($detailRows),
                ];
            }

            $rows[] = array_fill_keys(array_keys($rows[0]), '');
            $this->rowIndex++;
        }

        return new Collection($rows);
    }
     public function headings(): array
    {
        return [
            'Order Code','Order Date','Delivery Date',
            'Customer','Salesman',
            'Item','UOM Name','Item Price',
            'Quantity','Net','Excise Detail','Detail VAT','Detail Total'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $sheet->setShowSummaryBelow(false);

                foreach ($this->groups as $group) {
                    for ($r = $group['start']; $r <= $group['end']; $r++) {
                        $sheet->getRowDimension($r)
                              ->setOutlineLevel(1)
                              ->setVisible(false);
                    }
                    $sheet->getRowDimension($group['end'])->setCollapsed(true);
                }
            }
        ];
    }
}
