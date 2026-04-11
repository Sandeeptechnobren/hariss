<?php

namespace App\Exports;

use App\Models\Hariss_Transaction\Web\HTInvoiceHeader;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HtInvoiceFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $from_date;
    protected $to_date;
    protected $warehouseIds;
    protected $salesmanIds;

    public function __construct($from_date = null, $to_date = null, $warehouseIds = [], $salesmanIds = [])
    {
        $this->from_date = $from_date ?: now()->toDateString();
        $this->to_date   = $to_date   ?: now()->toDateString();
        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    public function collection()
    {
        $query = HTInvoiceHeader::with([
            'customer',
            'salesman',
            'company',
            'warehouse',
            'poorder',
            'order',
            'delivery'
        ]);

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }
        if ($this->from_date && $this->to_date) {
            $query->whereBetween('invoice_date', [$this->from_date, $this->to_date]);
        }
        // dd($query->count());
        $invoices = $query->get();
        $rows = [];

        foreach ($invoices as $i) {
            $rows[] = [
                'Invoice Code'      => (string)  $i->invoice_code,
                'Invoice Date' => $i->invoice_date
                    ? \Carbon\Carbon::parse($i->invoice_date)->format('d M Y')
                    : '',

                'Invoice Time' => $i->invoice_time
                    ? \Carbon\Carbon::parse($i->invoice_time)->format('h:i A')
                    : '',
                // 'PurchaseOrder Code' => (string) ($i->poorder->order_code ?? ''),
                // 'Order Code'        => (string) ($i->order->order_code ?? ''),
                'Customer'     => trim(($i->customer->osa_code ?? '') . ' - ' . ($i->customer->business_name ?? '')),
                'Salesman'     => trim(($i->salesman->osa_code ?? '') . ' - ' . ($i->salesman->name ?? '')),
                // 'Company Code'      => (string) ($header->company->company_code ?? ''),
                // 'Company Name'      => (string) ($header->company->company_name ?? ''),
                'Distributor'    =>  trim(($i->warehouse->warehouse_code ?? '') . ' - ' . ($i->warehouse->warehouse_name ?? '')),
                'Order Number'      => optional($i->order)->order_code ?? '',
                'Delivery Number'   => optional($i->delivery)->delivery_code ?? '',
                // 'Delivery Code'     => (string)  ($i->delivery->delivery_code ?? ''),
                'VAT'               => (float) $i->vat,
                'Excise'            => (float) $i->excise,
                'Net'               => (float) $i->net,
                'Total'             => (float) $i->total,
            ];
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
            // 'Company Code'      => '',
            // 'Company Name'      => '',
            'Distributor',
            'Order Number',
            'Delivery Number',
            // 'Delivery Code',
            'VAT',
            'Excise',
            'Net',
            'Total',

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
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
