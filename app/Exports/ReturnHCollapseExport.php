<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HtReturnHeader;
use App\Models\Hariss_Transaction\Web\HtReturnDetail;
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

class ReturnHCollapseExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected array $groupIndexes = [];
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($fromDate = null, $toDate = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function collection()
    {
        $rows = [];
        $rowIndex = 2;

        $query = HtReturnHeader::with([
            'customer',
            'company',
            'warehouse',
            'driver',
        ]);

        if ($this->fromDate && $this->toDate) {
            $query->whereDate('created_at', '>=', $this->fromDate)
                ->whereDate('created_at', '<=', $this->toDate);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }
// dd($query->count());
        $headers = $query->get();

        foreach ($headers as $header) {

            $headerRowIndex = $rowIndex;
            $rows[] = [
                'OSA Code'          => $header->return_code,
                'Customer'          => trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->business_name ?? '')),
                'Company'           => trim(($header->company->company_code ?? '') . ' - ' . ($header->company->company_name ?? '')),
                'Warehouse'         => trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                'Driver'            => trim(($header->driver->osa_code ?? '') . ' - ' . ($header->driver->driver_name ?? '')),
                'Turnman'           => $header->turnman,
                'Truck No'          => $header->truck_no,
                'Vat'               => $header->vat,
                'Net'               => $header->net,
                'Header Total'      => $header->total,
                'Contact No'        => $header->contact_no,

                'Item'              => '',
                'UOM Name'          => '',
                'Qty'               => '',
                'Expiry Date'       => '',
                'Batch No'          => '',
                'Return Type'       => '',
                'Reason'            => '',
                'Item Value'        => '',
                'Net Detail'        => '',
                'Detail VAT'        => '',
                'Detail Total'      => '',
            ];

            $rowIndex++;
            $details = HtReturnDetail::with(['item', 'uomdetails'])
                ->where('header_id', $header->id)
                ->get();

            $detailRowIndexes = [];

            foreach ($details as $detail) {

                $rows[] = [
                    'OSA Code'          => '',
                    'Customer'          => '',
                    'Company'           => '',
                    'Warehouse'         => '',
                    'Driver'            => '',
                    'Turnman'           => '',
                    'Truck No'          => '',
                    'Vat'               => '',
                    'Net'               => '',
                    'Header Total'      => '',
                    'Contact No'        => '',

                    'Item'              => trim(($header->item->erp_code ?? '') . ' - ' . ($header->item->name ?? '')),
                    'UOM Name'          => $detail->uomdetails->name ?? '',
                    'Qty'               => (float) $detail->qty,
                    'Expiry Date'       => $detail->actual_expiry_date,
                    'Batch No'          => $detail->batch_no,
                    'Return Type'       => $detail->return_type,
                    'Reason'            => $detail->return_reason,
                    'Item Value'        => (float) $detail->item_value,
                    'Net Detail'        => (float) $detail->net,
                    'Detail VAT'        => (float) $detail->vat,
                    'Detail Total'      => (float) $detail->total,
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
            'OSA Code',
            'Customer',
            'Company',
            'Warehouse',
            'Driver',
            'Turnman',
            'Truck No',
            'Vat',
            'Net',
            'Header Total',
            'Contact No',

            'Item',
            'UOM Name',
            'Qty',
            'Expiry Date',
            'Batch No',
            'Return Type',
            'Reason',
            'Item Value',
            'Net Detail',
            'Detail VAT',
            'Detail Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
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
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            }
        ];
    }
}
