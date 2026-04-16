<?php

// namespace App\Exports;

// use App\Models\Agent_Transaction\InvoiceDetail;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use Carbon\Carbon;

// class ItemWiseInvoiceExport implements FromCollection, ShouldAutoSize, WithEvents
// {
//     protected $itemId;
//     protected $fromDate;
//     protected $toDate;

//     protected array $groupIndexes = [];
//     protected int $rowIndex = 2;

//     public function __construct($itemId, $fromDate = null, $toDate = null)
//     {
//         $this->itemId  = $itemId;
//         $this->fromDate = $fromDate;
//         $this->toDate   = $toDate;
//     }

//     public function collection()
//     {
//         $rows = [];

//         // ✅ HEADER
//         $rows[] = [
//             'Invoice Code',
//             'Invoice Date',
//             'Warehouse',
//             'Customer',
//             'Salesman',
//             'Item',
//             'UOM',
//             'Quantity',
//             'Item Value',
//             'VAT',
//             'Net Total',
//             'Item Total',
//         ];

//         $this->rowIndex = 2;

//         // ✅ DATE LOGIC (same as PO)
//         $fromDate = $this->fromDate
//             ? Carbon::parse($this->fromDate)->startOfDay()
//             : Carbon::now()->startOfMonth();

//         $toDate = $this->toDate
//             ? Carbon::parse($this->toDate)->endOfDay()
//             : Carbon::now()->endOfMonth();

//         // ✅ FETCH DATA
//         $details = InvoiceDetail::with([
//                 'header.warehouse',
//                 'header.customer',
//                 'header.salesman',
//                 'item',
//                 'itemuom',
//             ])
//             ->where('item_id', $this->itemId)
//             ->whereNull('invoice_details.deleted_at')
//             ->whereHas('header', function ($q) use ($fromDate, $toDate) {
//                 $q->whereNull('deleted_at')
//                   ->whereBetween('invoice_date', [$fromDate, $toDate]);
//             })
//             ->orderBy('header_id')
//             ->get()
//             ->groupBy('header_id');

//         foreach ($details as $headerId => $items) {

//             $invoice = $items->first()->header;

//             $headerRow = $this->rowIndex;

//             // ✅ HEADER ROW
//             $rows[] = [
//                 $invoice->invoice_code,
//                 optional($invoice->invoice_date)->format('Y-m-d'),
//                 trim(($invoice->warehouse->warehouse_code ?? '') . ' - ' . ($invoice->warehouse->warehouse_name ?? '')),
//                 trim(($invoice->customer->osa_code ?? '') . ' - ' . ($invoice->customer->name ?? '')),
//                 trim(($invoice->salesman->osa_code ?? '') . ' - ' . ($invoice->salesman->name ?? '')),
//                 '',
//                 '',
//                 '',
//                 '',
//                 '',
//                 '',
//                 '',
//             ];

//             $this->rowIndex++;

//             $start = $this->rowIndex;

//             // 🔽 DETAIL HEADER
//             $rows[] = [
//                 '',
//                 'Item',
//                 'UOM',
//                 'Quantity',
//                 'Price',
//                 'VAT',
//                 'Net Total',
//                 'Item Total',
//                 '',
//                 '',
//                 '',
//                 '',
//             ];

//             $this->rowIndex++;

//             // 🔽 DETAIL ROWS
//             foreach ($items as $detail) {
//                 $rows[] = [
//                     '',
//                     trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
//                     $detail->itemuom->name ?? '',
//                     $detail->quantity,
//                     $detail->itemvalue,
//                     $detail->vat,
//                     $detail->net_total,
//                     $detail->item_total,
//                     '',
//                     '',
//                     '',
//                     '',
//                 ];
//                 $this->rowIndex++;
//             }

//             // ✅ GROUP INDEX (SAME AS YOUR WORKING FILE)
//             if ($start + 1 < $this->rowIndex) {
//                 $this->groupIndexes[] = [
//                     'header_row' => $headerRow,
//                     'start'      => $start,
//                     'end'        => $this->rowIndex - 1,
//                 ];
//             }

//             // Spacer
//             $rows[] = array_fill(0, 12, '');
//             $this->rowIndex++;
//         }

//         return collect($rows);
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function ($event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 // ✅ HEADER STYLE
//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => ['borderStyle' => Border::BORDER_THIN],
//                     ],
//                 ]);

//                 // ✅ COLLAPSE (EXACT SAME LOGIC)
//                 foreach ($this->groupIndexes as $group) {

//                     $sheet->getRowDimension($group['header_row'])
//                         ->setOutlineLevel(0)
//                         ->setVisible(true);

//                     for ($i = $group['start']; $i <= $group['end']; $i++) {
//                         $sheet->getRowDimension($i)->setOutlineLevel(1);
//                         $sheet->getRowDimension($i)->setVisible(false);
//                         $sheet->getStyle("B{$i}")->getAlignment()->setIndent(1);
//                     }

//                     $sheet->getStyle("B{$group['start']}:H{$group['start']}")
//                         ->getFont()->setBold(true);
//                 }

//                 $sheet->setShowSummaryBelow(false);
//             }
//         ];
//     }
// }
namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class ItemWiseInvoiceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $itemId;
    protected $fromDate;
    protected $toDate;

    public function __construct($itemId, $fromDate = null, $toDate = null)
    {
        $this->itemId   = $itemId;
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    public function collection()
    {
        $fromDate = $this->fromDate
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::now()->startOfMonth();

        $toDate = $this->toDate
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::now()->endOfMonth();

        // Fetch unique headers that have this item in their details
        $details = InvoiceDetail::with([
                'header.warehouse',
                'header.route',
                'header.salesman',
            ])
            ->where('item_id', $this->itemId)
            ->whereNull('invoice_details.deleted_at')
            ->whereHas('header', function ($q) use ($fromDate, $toDate) {
                $q->whereNull('deleted_at')
                  ->whereBetween('invoice_date', [$fromDate, $toDate]);
            })
            ->orderBy('header_id')
            ->get()
            ->groupBy('header_id');
        
        return $details->map(function ($items) {
            $invoice = $items->first()->header;

            return [
                'Code'         => $invoice->invoice_code ?? 'N/A',
                'Invoice Date' => optional($invoice->invoice_date)->format('d M Y') ?? 'N/A',
                'Distributor'  => trim(
                                    ($invoice->warehouse->warehouse_code ?? '') . ' - ' .
                                    ($invoice->warehouse->warehouse_name ?? '')
                                  ) ?: 'N/A',
                'Route'        => trim(
                                    ($invoice->route->route_code ?? '') . ' - ' .
                                    ($invoice->route->route_name ?? '')
                                  ) ?: 'N/A',
                'Sales Team'   => trim(
                                    ($invoice->salesman->osa_code ?? '') . ' - ' .
                                    ($invoice->salesman->name ?? '')
                                  ) ?: 'N/A',
                'Total'        => number_format((float) ($invoice->total_amount ?? 0), 2, '.', ','),
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Invoice Date',
            'Distributor',
            'Route',
            'Sales Team',
            'Total',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow    = $sheet->getHighestRow();

                // Header styling
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size'  => 11,
                        'name'  => 'Arial',
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
                            'color'       => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ]);

                // Data rows styling
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

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}