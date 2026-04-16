<?php

namespace App\Exports;

use App\Models\AgentCustomer;
use App\Models\Agent_Transaction\InvoiceHeader;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};

class InvoiceAgentCustomerExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents,
    WithStyles
{
    protected $uuid;
    protected $from;
    protected $to;

    public function __construct($uuid = null, $from = null, $to = null)
    {
        $this->uuid = $uuid;
        $this->from = $from;
        $this->to   = $to;
    }

    public function collection()
    {
        $rows = [];

        $customer = AgentCustomer::where('uuid', trim($this->uuid))->first();
        if (!$customer) {
            return new Collection([]);
        }

        $headers = InvoiceHeader::with([
            'warehouse',
            'route',
            'customer',
            'salesman',
            'details'
        ])
        ->where('customer_id', $customer->id)
        ->when($this->from && $this->to, function ($q) {
            $q->whereBetween('created_at', [$this->from, $this->to]);
        }, function ($q) {
            $q->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        })
        ->get();

        foreach ($headers as $header) {

            $rows[] = [
                $header->invoice_code,
                optional($header->invoice_date)->format('Y-m-d'),
                trim(($header->warehouse->warehouse_code ?? '') . ' - ' . ($header->warehouse->warehouse_name ?? '')),
                trim(($header->route->route_code ?? '') . ' - ' . ($header->route->route_name ?? '')),
                trim(($header->customer->osa_code ?? '') . ' - ' . ($header->customer->name ?? '')),
                trim(($header->salesman->osa_code ?? '') . ' - ' . ($header->salesman->name ?? '')),
                $header->details->count(),
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Invoice Date',
            'Distributor',
            'Route',
            'Customer',
            'Sales Team',
            'Item Count',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}