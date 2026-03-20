<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class InvoiceWarehouseCollapseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected int $warehouseId;
    protected array $groupIndexes = [];
    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(int $warehouseId, ?string $startDate = null, ?string $endDate = null)
    {
        $this->warehouseId = $warehouseId;
        $this->startDate   = $startDate;
        $this->endDate     = $endDate;
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Warehouse',
            'Route',
            'Customer',
            'Salesman',
            'Invoice Date',
            'Invoice Time',
            'Item',
            'UOM Name',
            'Quantity',
            'Item Value',
            'Detail VAT',
            'Detail Net Total',
            'Item Total',
            'Item CTN Price',
            'Item PCS Price',
        ];
    }

    protected function emptyRow(): array
    {
        return array_fill_keys($this->headings(), '');
    }

    public function collection(): Collection
    {
        $rows     = [];
        $rowIndex = 2; 

        $query = InvoiceHeader::with([
            'warehouse',
            'route',
            'customer',
            'salesman',
            'details.item',
            'details.itemuom',
            'details.itemprice',
        ])->where('warehouse_id', $this->warehouseId);

        if ($this->startDate) {
            $query->whereDate('invoice_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('invoice_date', '<=', $this->endDate);
        }

        $invoices = $query->orderBy('id')->get();

        foreach ($invoices as $invoice) {
            $headerRowIndex = $rowIndex;
            $headerRow      = $this->emptyRow();

            $headerRow['Invoice Code'] = $invoice->invoice_code;
            $headerRow['Warehouse']    = trim(
                ($invoice->warehouse->warehouse_code ?? '') . ' - ' .
                ($invoice->warehouse->warehouse_name ?? '')
            );
            $headerRow['Route'] = trim(
                ($invoice->route->route_code ?? '') . ' - ' .
                ($invoice->route->route_name ?? '')
            );
            $headerRow['Customer'] = trim(
                ($invoice->customer->osa_code ?? '') . ' - ' .
                ($invoice->customer->name ?? '')
            );
            $headerRow['Salesman'] = trim(
                ($invoice->salesman->osa_code ?? '') . ' - ' .
                ($invoice->salesman->name ?? '')
            );
            $headerRow['Invoice Date'] = $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date)->format('Y-m-d')
                : '';
            $headerRow['Invoice Time'] = $invoice->invoice_time
                ? Carbon::parse($invoice->invoice_time)->format('H:i:s')
                : '';

            $rows[] = $headerRow;
            $rowIndex++;
            $detailRowIndexes = [];

            foreach ($invoice->details as $detail) {
                $detailRow = $this->emptyRow();

                $detailRow['Item'] = trim(
                    ($detail->item->erp_code ?? '') . ' - ' .
                    ($detail->item->name ?? '')
                );
                $detailRow['UOM Name']         = $detail->uoms->name ?? '';
                $detailRow['Quantity']         = $detail->quantity;
                $detailRow['Item Value']       = $detail->itemvalue;
                $detailRow['Detail VAT']       = $detail->vat;
                $detailRow['Detail Net Total'] = $detail->net_total;
                $detailRow['Item Total']       = $detail->item_total;
                $detailRow['Item CTN Price']   = $detail->itemprice->buom_ctn_price ?? '';
                $detailRow['Item PCS Price']   = $detail->itemprice->auom_pc_price ?? '';

                $rows[] = $detailRow;
                $detailRowIndexes[] = $rowIndex;
                $rowIndex++;
            }
            if (!empty($detailRowIndexes)) {
                $this->groupIndexes[] = [
                    'start' => $headerRowIndex + 1,
                    'end'   => max($detailRowIndexes),
                ];
            }

            $rows[] = $this->emptyRow();
            $rowIndex++;
        }

        return new Collection($rows);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
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

                $sheet->getRowDimension(1)->setRowHeight(25);
                foreach ($this->groupIndexes as $group) {
                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)->setOutlineLevel(1);
                        $sheet->getRowDimension($i)->setVisible(false);
                    }
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}
