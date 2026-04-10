<?php

// namespace App\Exports;

// use App\Models\Hariss_Transaction\Web\PoOrderHeader;
// use App\Models\Hariss_Transaction\Web\PoOrderDetail;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\DataAccessHelper;
// use Illuminate\Support\Facades\Auth;

// class PoOrderCollapseExport implements
//     FromCollection,
//     WithHeadings,
//     ShouldAutoSize,
//     WithEvents,
//     WithStyles
// {

//     protected array $groups = [];
//     protected int $rowIndex = 2;

//     protected $fromDate;
//     protected $toDate;
//     protected $customerId;
//     protected $warehouseIds;
//     protected $salesmanIds;

//     public function __construct(
//         $fromDate = null,
//         $toDate = null,
//         $customerId = null,
//         $warehouseIds = [],
//         $salesmanIds = []
//     ) {
//         $this->fromDate = $fromDate ?: now()->toDateString();
//         $this->toDate   = $toDate   ?: now()->toDateString();
//         $this->customerId   = $customerId;
//         $this->warehouseIds = $warehouseIds;
//         $this->salesmanIds  = $salesmanIds;
//     }

//     public function collection()
//     {
//         $rows = [];

//         $query = PoOrderHeader::with([
//             'customer:id,osa_code,business_name',
//             'salesman:id,osa_code,name'
//         ]);

//         if ($this->fromDate && $this->toDate) {
//             $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);
//         }

//         if (!empty($this->customerId)) {
//             $query->where('customer_id', $this->customerId);
//         }

//         if (!empty($this->warehouseIds)) {
//             $query->whereIn('warehouse_id', $this->warehouseIds);
//         }

//         if (!empty($this->salesmanIds)) {
//             $query->whereIn('salesman_id', $this->salesmanIds);
//         }
//         $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
//         $headers = $query->get();
//         if ($headers->isEmpty()) {
//             return new Collection([]);
//         }
//         $details = PoOrderDetail::with(['item', 'uom'])->whereIn('header_id', $headers->pluck('id'))->get()->groupBy('header_id');
//         foreach ($headers as $header) {
//             $rows[] = [
//                 'Order Code'     => $header->order_code,
//                 'Order Date'     => optional($header->order_date)->format('Y-m-d'),
//                 'Delivery Date'  => optional($header->delivery_date)->format('Y-m-d'),
//                 'Customer'       => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
//                 'Salesman'       => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
//                 'Net Amount'     => number_format((float)$header->net, 2, '.', ','),
//                 'VAT'            => number_format((float)$header->vat, 2, '.', ','),
//                 'Total'          => number_format((float)$header->total, 2, '.', ','),

//                 'Item'           => '',
//                 'UOM Name'       => '',
//                 'Item Price'     => '',
//                 'Quantity'       => '',
//                 'Net'            => '',
//                 'Excise Detail'  => '',
//                 'Detail VAT'     => '',
//                 'Detail Total'   => '',
//             ];
//             $this->rowIndex++;
//             $detailRows = [];
//             foreach ($details[$header->id] ?? [] as $detail) {
//                 $rows[] = [
//                     'Order Code'     => '',
//                     'Order Date'     => '',
//                     'Delivery Date'  => '',
//                     'Customer'       => '',
//                     'Salesman'       => '',
//                     'Net Amount'     => '',
//                     'VAT'            => '',
//                     'Total'          => '',
//                     'Item'           => trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
//                     'UOM Name'       => $detail->uom->name ?? '',
//                     'Item Price'     => number_format((float)$detail->item_price, 2, '.', ','),
//                     'Quantity'       => $detail->quantity,
//                     'Net'            => number_format((float)$detail->net, 2, '.', ','),
//                     'Excise Detail'  => number_format((float)$detail->excise, 2, '.', ','),
//                     'Detail VAT'     => number_format((float)$detail->vat, 2, '.', ','),
//                     'Detail Total'   => number_format((float)$detail->total, 2, '.', ','),
//                 ];
//                 $detailRows[] = $this->rowIndex;
//                 $this->rowIndex++;
//             }
//             if (!empty($detailRows)) {
//                 $this->groups[] = [
//                     'start' => min($detailRows),
//                     'end'   => max($detailRows),
//                 ];
//             }
//             $rows[] = array_fill_keys(array_keys($rows[0]), '');
//             $this->rowIndex++;
//         }
//         return new Collection($rows);
//     }
//     public function headings(): array
//     {
//         return [
//             'Order Code',
//             'Order Date',
//             'Delivery Date',
//             'Customer',
//             'Salesman',
//             'Net Amount',
//             'VAT',
//             'Total',
//             'Item',
//             'UOM Name',
//             'Item Price',
//             'Quantity',
//             'Net',
//             'Excise Detail',
//             'Detail VAT',
//             'Detail Total'
//         ];
//     }

//     public function styles(Worksheet $sheet)
//     {
//         $sheet->getStyle('A1:P1')->getFont()->setBold(true);
//         $sheet->getStyle('A1:P1')->getAlignment()
//             ->setHorizontal(Alignment::HORIZONTAL_CENTER);
//     }

//     public function registerEvents(): array
//     {
//         return [

//             AfterSheet::class => function (AfterSheet $event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'font' => [
//                         'bold' => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                         ],
//                     ],
//                 ]);

//                 $sheet->setShowSummaryBelow(false);

//                 foreach ($this->groups as $group) {

//                     for ($r = $group['start']; $r <= $group['end']; $r++) {
//                         $sheet->getRowDimension($r)
//                             ->setOutlineLevel(1)
//                             ->setVisible(false);
//                     }

//                     $sheet->getRowDimension($group['end'])->setCollapsed(true);
//                 }
//             }

//         ];
//     }
// }
// namespace App\Exports;

// use App\Models\Hariss_Transaction\Web\PoOrderHeader;
// use App\Models\Hariss_Transaction\Web\PoOrderDetail;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\DataAccessHelper;
// use Illuminate\Support\Facades\Auth;

// class PoOrderCollapseExport implements
//     FromCollection,
//     ShouldAutoSize,
//     WithEvents,
//     WithStyles
// {

//     protected array $groups = [];
//     protected int $rowIndex = 1;

//     protected $fromDate;
//     protected $toDate;
//     protected $customerId;
//     protected $warehouseIds;
//     protected $salesmanIds;

//     public function __construct(
//         $fromDate = null,
//         $toDate = null,
//         $customerId = null,
//         $warehouseIds = [],
//         $salesmanIds = []
//     ) {
//         $this->fromDate = $fromDate ?: now()->toDateString();
//         $this->toDate   = $toDate   ?: now()->toDateString();
//         $this->customerId   = $customerId;
//         $this->warehouseIds = $warehouseIds;
//         $this->salesmanIds  = $salesmanIds;
//     }

//     public function collection()
//     {
//         $rows = [];

//         $query = PoOrderHeader::with([
//             'customer:id,osa_code,business_name',
//             'salesman:id,osa_code,name'
//         ]);

//         if ($this->fromDate && $this->toDate) {
//             $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);
//         }

//         if (!empty($this->customerId)) {
//             $query->where('customer_id', $this->customerId);
//         }

//         if (!empty($this->warehouseIds)) {
//             $query->whereIn('warehouse_id', $this->warehouseIds);
//         }

//         if (!empty($this->salesmanIds)) {
//             $query->whereIn('salesman_id', $this->salesmanIds);
//         }

//         $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
//         $headers = $query->get();

//         if ($headers->isEmpty()) {
//             return new Collection([]);
//         }

//         $details = PoOrderDetail::with(['item', 'uom'])
//             ->whereIn('header_id', $headers->pluck('id'))
//             ->get()
//             ->groupBy('header_id');

//         foreach ($headers as $header) {

//             // HEADER TITLES
//             $rows[] = [
//                 'Order Code',
//                 'Order Date',
//                 'Delivery Date',
//                 'Customer',
//                 'Salesman',
//                 'Net Amount',
//                 'VAT',
//                 'Total',
//             ];
//             $this->rowIndex++;

//             // HEADER DATA
//             $rows[] = [
//                 $header->order_code,
//                 optional($header->order_date)->format('Y-m-d'),
//                 optional($header->delivery_date)->format('Y-m-d'),
//                 trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
//                 trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
//                 number_format((float)$header->net, 2, '.', ','),
//                 number_format((float)$header->vat, 2, '.', ','),
//                 number_format((float)$header->total, 2, '.', ','),
//             ];
//             $this->rowIndex++;

//             // DETAIL TITLES
//             $rows[] = [
//                 'Item',
//                 'UOM',
//                 'Price',
//                 'Qty',
//                 'Net',
//                 'Excise',
//                 'VAT',
//                 'Total',
//             ];
//             $this->rowIndex++;

//             $start = $this->rowIndex;

//             // DETAIL DATA
//             foreach ($details[$header->id] ?? [] as $detail) {
//                 $rows[] = [
//                     trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
//                     $detail->uom->name ?? '',
//                     number_format((float)$detail->item_price, 2, '.', ','),
//                     $detail->quantity,
//                     number_format((float)$detail->net, 2, '.', ','),
//                     number_format((float)$detail->excise, 2, '.', ','),
//                     number_format((float)$detail->vat, 2, '.', ','),
//                     number_format((float)$detail->total, 2, '.', ','),
//                 ];
//                 $this->rowIndex++;
//             }

//             $end = $this->rowIndex - 1;

//             if ($end >= $start) {
//                 $this->groups[] = [
//                     'start' => $start,
//                     'end'   => $end,
//                 ];
//             }
//         }

//         return new Collection($rows);
//     }

//     public function styles(Worksheet $sheet)
//     {
//         return [];
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function (AfterSheet $event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();
//                 $lastRow = $sheet->getHighestRow();

//                 $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                         ],
//                     ],
//                 ]);

//                 for ($i = 1; $i <= $lastRow; $i++) {

//                     $value = $sheet->getCell("A{$i}")->getValue();

//                     if (in_array($value, ['Order Code', 'Item'])) {
//                         $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->applyFromArray([
//                             'fill' => [
//                                 'fillType' => Fill::FILL_SOLID,
//                                 // 'startColor' => ['rgb' => '993442'],
//                                 'startColor' => ['rgb' => 'A8E6A3'],
//                             ],
//                             'font' => [
//                                 'bold' => true,
//                                 'color' => ['rgb' => 'FFFFFF'],
//                             ],
//                             'alignment' => [
//                                 'horizontal' => Alignment::HORIZONTAL_CENTER,
//                             ],
//                         ]);
//                     }
//                 }

//                 foreach ($this->groups as $group) {
//                     for ($r = $group['start']; $r <= $group['end']; $r++) {
//                         $sheet->getRowDimension($r)
//                             ->setOutlineLevel(1)
//                             ->setVisible(false);
//                     }
//                     $sheet->getRowDimension($group['end'])->setCollapsed(true);
//                 }

//                 $sheet->setShowSummaryBelow(false);
//             }
//         ];
//     }
// }

// namespace App\Exports;

// use App\Models\Hariss_Transaction\Web\PoOrderHeader;
// use App\Models\Hariss_Transaction\Web\PoOrderDetail;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\DataAccessHelper;
// use Illuminate\Support\Facades\Auth;

// class PoOrderCollapseExport implements FromCollection, ShouldAutoSize, WithEvents
// {
//     protected array $groups = [];
//     protected int $rowIndex = 1;

//     public function collection()
//     {
//         $rows = [];

//         // HEADER TITLES (ONLY ONCE)
//         $rows[] = [
//             'Order Code','Order Date','Delivery Date','Customer','Salesman',
//             'Net Amount','VAT','Item Count','Total'
//         ];
//         $this->rowIndex++;

//         $headers = DataAccessHelper::filterAgentTransaction(
//             PoOrderHeader::with(['customer:id,osa_code,business_name','salesman:id,osa_code,name']),
//             Auth::user()
//         )->get();

//         $details = PoOrderDetail::with(['item','uom'])
//             ->whereIn('header_id', $headers->pluck('id'))
//             ->get()
//             ->groupBy('header_id');

//         foreach ($headers as $header) {

//             $detailList = $details[$header->id] ?? [];
//             $count = count($detailList);

//             // HEADER DATA
//             $rows[] = [
//                 $header->order_code,
//                 optional($header->order_date)->format('Y-m-d'),
//                 optional($header->delivery_date)->format('Y-m-d'),
//                 trim(($header->customer->osa_code ?? '').' - '.($header->customer->business_name ?? '')),
//                 trim(($header->salesman->osa_code ?? '').' - '.($header->salesman->name ?? '')),
//                 number_format((float)$header->net,2,'.',','),
//                 number_format((float)$header->vat,2,'.',','),
//                 $count,
//                 number_format((float)$header->total,2,'.',','),
//             ];
//             $this->rowIndex++;

//             // DETAIL TITLE (included in collapse)
//             $start = $this->rowIndex;

//             $rows[] = [
//                 '',
//                 'Item','UOM','Price','Qty','Net','Excise','VAT','Total'
//             ];
//             $this->rowIndex++;

//             foreach ($detailList as $d) {
//                 $rows[] = [
//                     '',
//                     trim(($d->item->erp_code ?? '').' - '.($d->item->name ?? '')),
//                     $d->uom->name ?? '',
//                     number_format((float)$d->item_price,2,'.',','),
//                     $d->quantity,
//                     number_format((float)$d->net,2,'.',','),
//                     number_format((float)$d->excise,2,'.',','),
//                     number_format((float)$d->vat,2,'.',','),
//                     number_format((float)$d->total,2,'.',','),
//                 ];
//                 $this->rowIndex++;
//             }

//             $this->groups[] = ['start'=>$start,'end'=>$this->rowIndex-1];
//         }

//         return new Collection($rows);
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function ($event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastCol = $sheet->getHighestColumn();
//                 $lastRow = $sheet->getHighestRow();

//                 // Borders
//                 $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
//                     'borders'=>['allBorders'=>['borderStyle'=>Border::BORDER_THIN]]
//                 ]);

//                 // HEADER TITLE (ROW 1 ONLY)
//                 $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
//                     'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'993442']],
//                     'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
//                     'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER],
//                 ]);

//                 // DETAIL TITLE STYLE
//                 for ($i=2;$i<=$lastRow;$i++) {
//                     if ($sheet->getCell("B{$i}")->getValue()==='Item') {
//                         $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
//                             'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'CCFFCC']],
//                             'font'=>['bold'=>true],
//                         ]);
//                     }
//                 }

//                 // GROUPING (includes detail title)
//                 foreach ($this->groups as $g) {
//                     for ($r=$g['start'];$r<=$g['end'];$r++) {
//                         $sheet->getRowDimension($r)->setOutlineLevel(1)->setVisible(false);
//                     }
//                     $sheet->getRowDimension($g['end'])->setCollapsed(true);
//                 }

//                 $sheet->setShowSummaryBelow(false);
//             }
//         ];
//     }
// }
namespace App\Exports;

use App\Models\Hariss_Transaction\Web\PoOrderHeader;
use App\Models\Hariss_Transaction\Web\PoOrderDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class PoOrderCollapseExport implements FromCollection, ShouldAutoSize, WithEvents
{
    protected array $groups = [];
    protected int $rowIndex = 1;

    protected $fromDate;
    protected $toDate;
    protected $customerId;
    protected $warehouseIds;
    protected $salesmanIds;
    public function __construct(
        $fromDate = null,
        $toDate = null,
        $customerId = null,
        $warehouseIds = [],
        $salesmanIds = []
    ) {
        $this->fromDate = $fromDate ?: now()->toDateString();
        $this->toDate   = $toDate   ?: now()->toDateString();
        $this->customerId   = $customerId;
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }
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
        $this->rowIndex++;
        $query = PoOrderHeader::with([
            'customer:id,osa_code,business_name',
            'salesman:id,osa_code,name'
        ]);
        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('order_date', [$this->fromDate, $this->toDate]);
        }
        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
        }
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }
        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }
        $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());
        $headers = $query->get();
        if ($headers->isEmpty()) {
            return new Collection([]);
        }
        $details = PoOrderDetail::with(['item', 'uom'])
            ->whereIn('header_id', $headers->pluck('id'))
            ->get()
            ->groupBy('header_id');
        foreach ($headers as $header) {
            $detailList = $details[$header->id] ?? [];
            $count = count($detailList);
            $rows[] = [
                $header->order_code,
                optional($header->order_date)->format('d M Y'),
                optional($header->delivery_date)->format('d M Y'),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                number_format((float)$header->vat, 2, '.', ','),
                number_format((float)$header->net, 2, '.', ','),
                $count,
                number_format((float)$header->total, 2, '.', ','),
            ];
            $this->rowIndex++;
            $start = $this->rowIndex;
            $rows[] = [
                '',
                'Item',
                'UOM',
                'Price',
                'Qty',
                'VAT',
                'Excise',
                'Net',
                'Total'
            ];
            $this->rowIndex++;
            foreach ($detailList as $d) {
                $rows[] = [
                    '',
                    trim(($d->item->erp_code ?? '') . ' - ' . ($d->item->name ?? '')),
                    $d->uom->name ?? '',
                    number_format((float)$d->item_price, 2, '.', ','),
                    $d->quantity,
                    number_format((float)$d->vat, 2, '.', ','),
                    number_format((float)$d->excise, 2, '.', ','),
                    number_format((float)$d->net, 2, '.', ','),
                    number_format((float)$d->total, 2, '.', ','),
                ];
                $this->rowIndex++;
            }
            $this->groups[] = [
                'start' => $start,
                'end' => $this->rowIndex - 1
            ];
            $rows[] = [''];
            $this->rowIndex++;
        }
        return new Collection($rows);
    }
    // public function registerEvents(): array
    //     {
    //         return [
    //             AfterSheet::class => function ($event) {
    //                 $sheet = $event->sheet->getDelegate();
    //                 $lastCol = $sheet->getHighestColumn();
    //                 $lastRow = $sheet->getHighestRow();

    //                 $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
    //                     'borders'=>[
    //                         'allBorders'=>[
    //                             'borderStyle'=>Border::BORDER_THIN
    //                         ]
    //                     ]
    //                 ]);

    //                 $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
    //                     'fill'=>[
    //                         'fillType'=>Fill::FILL_SOLID,
    //                         'startColor'=>['rgb'=>'993442']
    //                     ],
    //                     'font'=>[
    //                         'bold'=>true,
    //                         'color'=>['rgb'=>'FFFFFF']
    //                     ],
    //                     'alignment'=>[
    //                         'horizontal'=>Alignment::HORIZONTAL_CENTER
    //                     ],
    //                 ]);

    //                 // DETAIL TITLE STYLE (LIGHT GREEN)
    //                 // for ($i=2;$i<=$lastRow;$i++) {
    //                 //     if ($sheet->getCell("B{$i}")->getValue()==='Item') {
    //                 //         $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
    //                 //             'fill'=>[
    //                 //                 'fillType'=>Fill::FILL_SOLID,
    //                 //                 // 'startColor'=>['rgb'=>'CCFFCC']
    //                 //                 'startColor'=>['rgb'=>'993442']
    //                 //             ],
    //                 //             'font'=>[
    //                 //                 'bold'=>true,
    //                 //                 'color'=>['rgb'=>'FFFFFF']

    //                 //             ],
    //                 //         ]);
    //                 //     }
    //                 // }
    //                 for ($i=2;$i<=$lastRow;$i++) {
    //                     if ($sheet->getCell("B{$i}")->getValue()==='Item') {
    //                         $sheet->getStyle("B{$i}:{$lastCol}{$i}")->applyFromArray([
    //                             'fill'=>[
    //                                 'fillType'=>Fill::FILL_SOLID,
    //                                 // 'startColor'=>['rgb'=>'CCFFCC']
    //                                 'startColor'=>['rgb'=>'993442']
    //                             ],
    //                             'font'=>[
    //                                 'bold'=>true,
    //                                 'color'=>['rgb'=>'FFFFFF']
    //                             ],
    //                             'alignment'=>[
    //                                 'horizontal'=>Alignment::HORIZONTAL_CENTER
    //                             ],
    //                         ]);
    //                         $sheet->getStyle("A{$i}")->applyFromArray([
    //                             'fill'=>[
    //                                 'fillType'=>Fill::FILL_NONE
    //                             ],
    //                             'font'=>[
    //                                 'color'=>['rgb'=>'000000']
    //                             ],
    //                         ]);
    //                     }
    //                 }

    //                 foreach ($this->groups as $g) {
    //                     for ($r=$g['start'];$r<=$g['end'];$r++) {
    //                         $sheet->getRowDimension($r)
    //                             ->setOutlineLevel(1)
    //                             ->setVisible(false);
    //                     }
    //                     $sheet->getRowDimension($g['end'])->setCollapsed(true);
    //                 }

    //                 $sheet->setShowSummaryBelow(false);
    //             }
    //         ];
    //     }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // ❌ REMOVE ANY CUSTOM BORDER (agar pehle laga ho)
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_NONE,
                        ],
                    ],
                ]);

                // ✅ ENABLE DEFAULT EXCEL GRIDLINES (yehi main cheez hai)
                $sheet->setShowGridlines(true);

                // 🔹 TOP HEADER (same)
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442']
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ],
                ]);

                // 🔥 DETAIL HEADER → ONLY BOLD
                for ($i = 2; $i <= $lastRow; $i++) {

                    if ($sheet->getCell("B{$i}")->getValue() === 'Item') {

                        $sheet->getStyle("B{$i}:I{$i}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_NONE
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '000000']
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER
                            ],
                        ]);
                    }
                }

                // 🔹 GROUP COLLAPSE
                foreach ($this->groups as $g) {
                    for ($r = $g['start']; $r <= $g['end']; $r++) {
                        $sheet->getRowDimension($r)
                            ->setOutlineLevel(1)
                            ->setVisible(false);
                    }
                    $sheet->getRowDimension($g['end'])->setCollapsed(true);
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
