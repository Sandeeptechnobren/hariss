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
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class PoOrderCollapseExport implements
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

            $rows[] = [
                'Order Code'     => $header->order_code,
                'Order Date'     => optional($header->order_date)->format('Y-m-d'),
                'Delivery Date'  => optional($header->delivery_date)->format('Y-m-d'),
                'Customer'       => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Salesman'       => trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                'Net Amount'     => number_format((float)$header->net, 2, '.', ','),
                'VAT'            => number_format((float)$header->vat, 2, '.', ','),
                'Total'          => number_format((float)$header->total, 2, '.', ','),

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

            $detailRows = [];

            foreach ($details[$header->id] ?? [] as $detail) {

                $rows[] = [
                    'Order Code'     => '',
                    'Order Date'     => '',
                    'Delivery Date'  => '',
                    'Customer'       => '',
                    'Salesman'       => '',
                    'Net Amount'     => '',
                    'VAT'            => '',
                    'Total'          => '',

                    'Item'           => trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    'UOM Name'       => $detail->uom->name ?? '',
                    'Item Price'     => number_format((float)$detail->item_price, 2, '.', ','),
                    'Quantity'       => $detail->quantity,
                    'Net'            => number_format((float)$detail->net, 2, '.', ','),
                    'Excise Detail'  => number_format((float)$detail->excise, 2, '.', ','),
                    'Detail VAT'     => number_format((float)$detail->vat, 2, '.', ','),
                    'Detail Total'   => number_format((float)$detail->total, 2, '.', ','),
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
            'Order Code',
            'Order Date',
            'Delivery Date',
            'Customer',
            'Salesman',
            'Net Amount',
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
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getAlignment()
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
