<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class ItemPoOrderCollapseExport implements
    FromCollection,
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

    // public function collection()
    // {
    //     $rows = [];
        
    //     $rows[] = [
    //         'Order Code',
    //         'Order Date',
    //         'Delivery Date',
    //         'Customer',
    //         'Salesman',
    //         'Net Amount',
    //         'VAT',
    //         'Item Count',
    //         'Total'
    //     ];

    //     $this->rowIndex = 2;

    //     $fromDate = $this->fromDate
    //         ? Carbon::parse($this->fromDate)->startOfDay()
    //         : Carbon::now()->startOfMonth();

    //     $toDate = $this->toDate
    //         ? Carbon::parse($this->toDate)->endOfDay()
    //         : Carbon::now()->endOfMonth();

    //     $query = PoOrderHeader::with([
    //         'customer:id,osa_code,business_name',
    //         'salesman:id,osa_code,name'
    //     ]);

    //     if (!empty($this->itemId)) {
    //         $query->whereHas('details', function ($q) use ($fromDate, $toDate) {
    //             $q->where('item_id', $this->itemId)
    //               ->whereBetween('created_at', [$fromDate, $toDate]);
    //         });
    //     } else {
    //         $query->whereHas('details', function ($q) use ($fromDate, $toDate) {
    //             $q->whereBetween('created_at', [$fromDate, $toDate]);
    //         });
    //     }

    //     if (!empty($this->customerId)) {
    //         $query->where('customer_id', $this->customerId);
    //     }

    //     $headers = $query->get();

    //     if ($headers->isEmpty()) {
    //         return collect([]);
    //     }

    //     $details = PoOrderDetail::with(['item', 'uom'])
    //         ->whereIn('header_id', $headers->pluck('id'))
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->when(!empty($this->itemId), function ($q) {
    //             $q->where('item_id', $this->itemId);
    //         })
    //         ->get()
    //         ->groupBy('header_id');

    //     foreach ($headers as $header) {

    //         $detailList = $details[$header->id] ?? [];
    //         $count = count($detailList);

    //         $rows[] = [
    //             $header->order_code,
    //             optional($header->order_date)->format('d M Y'),
    //             optional($header->delivery_date)->format('d M Y'),
    //             trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
    //             trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
    //             number_format((float)$header->net, 2),
    //             number_format((float)$header->vat, 2),
    //             $count,
    //             number_format((float)$header->total, 2),
    //         ];

    //         $this->rowIndex++;

    //         $start = $this->rowIndex;

    //         $rows[] = [
    //             '',
    //             'Item',
    //             'UOM',
    //             'Price',
    //             'Qty',
    //             'VAT',
    //             'Excise',
    //             'Net',
    //             'Total'
    //         ];

    //         $this->rowIndex++;

    //         foreach ($detailList as $d) {
    //             $rows[] = [
    //                 '',
    //                 trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
    //                 $d->uom->name ?? '',
    //                 number_format((float)$d->item_price, 2),
    //                 $d->quantity,
    //                 number_format((float)$d->vat, 2),
    //                 number_format((float)$d->excise, 2),
    //                 number_format((float)$d->net, 2),
    //                 number_format((float)$d->total, 2),
    //             ];
    //             $this->rowIndex++;
    //         }

    //         if ($count > 0) {
    //             $this->groups[] = [
    //                 'start' => $start,
    //                 'end'   => $this->rowIndex - 1
    //             ];
    //         }

    //         // Spacer
    //         $rows[] = [''];
    //         $this->rowIndex++;
    //     }

    //     return collect($rows);
    // }


//     public function collection()
// {
//     $rows = [];

//     $rows[] = [
//         'Order Code',
//         'Order Date',
//         'Delivery Date',
//         'Customer',
//         'Salesman',
//         'Net Amount',
//         'VAT',
//         'Item Count',
//         'Total'
//     ];

//     $this->rowIndex = 2;

//     // Use Carbon instances directly (already parsed in controller)
//     $fromDate = $this->fromDate instanceof \Carbon\Carbon
//         ? $this->fromDate
//         : \Carbon\Carbon::parse($this->fromDate)->startOfDay();

//     $toDate = $this->toDate instanceof \Carbon\Carbon
//         ? $this->toDate
//         : \Carbon\Carbon::parse($this->toDate)->endOfDay();

//     $query = PoOrderHeader::with([
//         'customer:id,osa_code,business_name',
//         'salesman:id,osa_code,name'
//     ]);

//     // Mirror exact logic from getByItem()
//     if (!empty($this->itemId)) {
//         $query->whereHas('details', function ($q) use ($fromDate, $toDate) {
//             $q->where('item_id', $this->itemId)
//               ->whereBetween('created_at', [$fromDate, $toDate]);
//         });
//     } else {
//         $query->whereHas('details', function ($q) use ($fromDate, $toDate) {
//             $q->whereBetween('created_at', [$fromDate, $toDate]);
//         });
//     }

//     if (!empty($this->customerId)) {
//         $query->where('customer_id', $this->customerId);
//     }

//     $headers = $query->get();

//     // if ($headers->isEmpty()) {
//     //     return collect([]);
//     // }
//     if ($headers->isEmpty()) {
//         return collect($rows); // ← return rows which already has the header title
//     }

//     // Mirror exact detail filter from getByItem()
//     $details = PoOrderDetail::with(['item', 'uom'])
//         ->whereIn('header_id', $headers->pluck('id'))
//         ->when(!empty($this->itemId), function ($q) use ($fromDate, $toDate) {
//             // Both conditions together — same as getByItem's eager load
//             $q->where('item_id', $this->itemId)
//               ->whereBetween('created_at', [$fromDate, $toDate]);
//         })
//         ->when(empty($this->itemId), function ($q) use ($fromDate, $toDate) {
//             $q->whereBetween('created_at', [$fromDate, $toDate]);
//         })
//         ->get()
//         ->groupBy('header_id');

//     foreach ($headers as $header) {

//         $detailList = $details[$header->id] ?? collect([]);
//         $count      = count($detailList);

//         $rows[] = [
//             $header->order_code,
//             optional($header->order_date)->format('d M Y'),
//             optional($header->delivery_date)->format('d M Y'),
//             trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
//             trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
//             number_format((float)$header->net, 2),
//             number_format((float)$header->vat, 2),
//             $count,
//             number_format((float)$header->total, 2),
//         ];

//         $this->rowIndex++;

//         $start = $this->rowIndex;

//         $rows[] = ['', 'Item', 'UOM', 'Price', 'Qty', 'VAT', 'Excise', 'Net', 'Total'];
//         $this->rowIndex++;

//         foreach ($detailList as $d) {
//             $rows[] = [
//                 '',
//                 trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
//                 $d->uom->name ?? '',
//                 number_format((float)$d->item_price, 2),
//                 $d->quantity,
//                 number_format((float)$d->vat, 2),
//                 number_format((float)$d->excise, 2),
//                 number_format((float)$d->net, 2),
//                 number_format((float)$d->total, 2),
//             ];
//             $this->rowIndex++;
//         }

//         if ($count > 0) {
//             $this->groups[] = [
//                 'start' => $start,
//                 'end'   => $this->rowIndex - 1,
//             ];
//         }

//         $rows[] = [''];
//         $this->rowIndex++;
//     }
// return collect($rows);
// }

public function collection()
{
    $rows = [];

    $rows[] = [
        'Order Code',
        'Order Date',
        'Delivery Date',
        'Customer',
        'Salesman',
        'Net Amount',
        'VAT',
        'Item Count',
        'Total'
    ];

    $this->rowIndex = 2;

    $fromDate = $this->fromDate;
    $toDate   = $this->toDate;

    $query = PoOrderHeader::with([
        'customer:id,osa_code,business_name',
        'salesman:id,osa_code,name'
    ]);

    // ✅ item filter (detail relation)
    $query->whereHas('details', function ($q) {
        if (!empty($this->itemId)) {
            $q->where('item_id', $this->itemId);
        }
    });

    // ✅ date filter (HEADER TABLE — IMPORTANT)
    if ($fromDate && $toDate) {
        $query->whereBetween('order_date', [$fromDate, $toDate]);
    }

    // customer filter
    if (!empty($this->customerId)) {
        $query->where('customer_id', $this->customerId);
    }

    $headers = $query->get();

    if ($headers->isEmpty()) {
        return collect($rows);
    }

    // ✅ detail (NO DATE FILTER HERE)
    $details = PoOrderDetail::with(['item', 'uom'])
        ->whereIn('header_id', $headers->pluck('id'))
        ->when(!empty($this->itemId), function ($q) {
            $q->where('item_id', $this->itemId);
        })
        ->get()
        ->groupBy('header_id');

    foreach ($headers as $header) {

        $detailList = $details[$header->id] ?? collect([]);
        $count      = $detailList->count();

        $rows[] = [
            $header->order_code,
            optional($header->order_date)->format('d M Y'),
            optional($header->delivery_date)->format('d M Y'),
            trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
            trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
            number_format((float)$header->net, 2),
            number_format((float)$header->vat, 2),
            $count,
            number_format((float)$header->total, 2),
        ];

        $this->rowIndex++;

        $start = $this->rowIndex;

        $rows[] = ['', 'Item', 'UOM', 'Price', 'Qty', 'VAT', 'Excise', 'Net', 'Total'];
        $this->rowIndex++;

        foreach ($detailList as $d) {
            $rows[] = [
                '',
                trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                $d->uom->name ?? '',
                number_format((float)$d->item_price, 2),
                $d->quantity,
                number_format((float)$d->vat, 2),
                number_format((float)$d->excise, 2),
                number_format((float)$d->net, 2),
                number_format((float)$d->total, 2),
            ];
            $this->rowIndex++;
        }

        if ($count > 0) {
            $this->groups[] = [
                'start' => $start,
                'end'   => $this->rowIndex - 1,
            ];
        }

        $rows[] = [''];
        $this->rowIndex++;
    }

    return collect($rows);
}

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Header styling
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
                    ],
                ]);

                // Detail header styling
                for ($i = 2; $i <= $lastRow; $i++) {
                    if ($sheet->getCell("B{$i}")->getValue() === 'Item') {
                        $sheet->getStyle("B{$i}:I{$i}")->getFont()->setBold(true);
                    }
                }

                // Collapse logic
                foreach ($this->groups as $group) {
                    for ($r = $group['start']; $r <= $group['end']; $r++) {
                        $sheet->getRowDimension($r)
                              ->setOutlineLevel(1)
                              ->setVisible(false);
                    }
                    $sheet->getRowDimension($group['end'])->setCollapsed(true);
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}