<?php

namespace App\Exports;

use App\Models\Agent_Transaction\ReturnHeader;
use App\Models\AgentCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReturnAgentCustomerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;
    protected $startDate;
    protected $endDate;
    protected $groupIndexes = [];

    public function __construct($uuid = null, $startDate = null, $endDate = null)
    {
        $this->uuid      = $uuid;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function collection()
    {
        $rows     = [];
        $rowIndex = 2;

        $customer = AgentCustomer::where('uuid', trim($this->uuid))->first();

        if (!$customer) {
            return new Collection([]);
        }

        $query = ReturnHeader::with([
            'country',
            'order',
            'warehouse',
            'route',
            'salesman',
            'delivery',
            'customer',
            'details.item',
            'details.uom',
        ])->where('customer_id', $customer->id);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59',
            ]);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $details   = $header->details;
            $itemCount = $details->count();
            $headerRowIndex = $rowIndex;
            $rows[] = [
                $header->osa_code,
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $itemCount,

                '', '', '', '', '', '', '',
            ];

            $rowIndex++;
            $detailRowIndexes = [];
            foreach ($details as $detail) {

                $rows[] = [
                    '', '', '', '', '', '',

                    trim(($detail->item->erp_code ?? '') . ' - ' . ($detail->item->name ?? '')),
                    $detail->uom->name ?? '',
                    (float) $detail->item_price,
                    (float) $detail->item_quantity,
                    (float) $detail->vat,
                    (float) $detail->net_total,
                    (float) $detail->total,
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
            $rows[] = array_fill(0, 13, '');
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'Item Count',

            'Item',
            'UOM',
            'Item Price',
            'Item Quantity',
            'VAT (Detail)',
            'Net Total (Detail)',
            'Total (Detail)',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
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

                $sheet->getRowDimension(1)->setRowHeight(25);
                foreach ($this->groupIndexes as $group) {
                    $sheet->getRowDimension($group['start'])->setOutlineLevel(1);
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1)->setVisible(false);
                    }
                }
            },
        ];
    }
}
