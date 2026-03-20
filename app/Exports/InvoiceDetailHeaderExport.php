<?php

namespace App\Exports;

use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InvoiceDetailHeaderExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;
    protected $fromDate;
    protected $toDate;

    public function __construct($uuid = null, $fromDate = null, $toDate = null)
    {
        $this->uuid     = $uuid;
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
    }

    public function collection()
    {
        $rows = [];

        $query = InvoiceHeader::with([
            'company',
            'order',
            'delivery',
            'warehouse',
            'route',
            'customer',
            'salesman'
        ]);

        if ($this->uuid) {
            $query->where('uuid', $this->uuid);
        }

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('invoice_date', [$this->fromDate, $this->toDate]);
        } elseif ($this->fromDate) {
            $query->whereDate('invoice_date', '>=', $this->fromDate);
        } elseif ($this->toDate) {
            $query->whereDate('invoice_date', '<=', $this->toDate);
        }

        $headers = $query->get();

        foreach ($headers as $header) {

            $rows[] = [
                'Invoice Code'      => (string)$header->invoice_code,
                'Currency'          => (string)$header->currency_name,
                'Company'           => (string)optional($header->company)->company_name,
                'Order Code'        => (string)optional($header->order)->order_code,
                'Delivery Code'     => (string)optional($header->delivery)->delivery_code,
                'Warehouse Code'    => (string)optional($header->warehouse)->warehouse_code,
                'Warehouse Name'    => (string)optional($header->warehouse)->warehouse_name,
                'Route Code'        => (string)optional($header->route)->route_code,
                'Route Name'        => (string)optional($header->route)->route_name,
                'Customer Code'     => (string)optional($header->customer)->osa_code,
                'Customer Name'     => (string)optional($header->customer)->name,
                'Salesman Code'     => (string)optional($header->salesman)->osa_code,
                'Salesman Name'     => (string)optional($header->salesman)->name,
                'Latitude'          => (string)$header->latitude,
                'Longitude'         => (string)$header->longitude,
                'URA Invoice No'    => (string)$header->ura_invoice_no,
                'URA Antifake Code' => (string)$header->ura_antifake_code,
                'URA QR Code'       => (string)$header->ura_qr_code,
                'Purchaser Name'    => (string)$header->purchaser_name,
                'Purchaser Contact' => (string)$header->purchaser_contact,
                'Invoice Date'      => (string)$header->invoice_date,
                'Invoice Time'      => (string)$header->invoice_time,
                'Invoice Type'      => (string)$header->invoice_type,
                'Gross Total'       => (float)$header->gross_total,
                'VAT'               => (float)$header->vat,
                'Pre VAT'           => (float)$header->pre_vat,
                'Net Total'         => (float)$header->net_total,
                'Promotion Total'   => (float)$header->promotion_total,
                'Discount'          => (float)$header->discount,
                'Total Amount'      => (float)$header->total_amount,
                'Invoice Number'    => (string)$header->invoice_number,
                // 'Status'            => (string)($header->status ? 'Active' : 'Inactive'),

                'Item Code'                 => '',
                'Item Name'                 => '',
                'UOM Code'                  => '',
                'UOM Name'                  => '',
                'Quantity'                  => '',
                'Item Value'                => '',
                'VAT (Detail)'              => '',
                'Pre VAT (Detail)'          => '',
                'Net Total (Detail)'        => '',
                'Item Total'                => '',
                'Promotion Code'            => '',
                'Parent'                    => '',
                'Approver Name'             => '',
                'Approved Date'             => '',
                'Rejected By'               => '',
                'RM Action Date'            => '',
                'Comment For Rejection'     => '',
                'Detail Status'             => '',
            ];

            $details = InvoiceDetail::with(['item','itemuom','promotion','approver'])
                ->where('header_id', $header->id)
                ->get();

            foreach ($details as $detail) {
                $rows[] = [
                    'Invoice Code'      => '',
                    'Currency'          => '',
                    'Company'           => '',
                    'Order Code'        => '',
                    'Delivery Code'     => '',
                    'Warehouse Code'    => '',
                    'Warehouse Name'    => '',
                    'Route Code'        => '',
                    'Route Name'        => '',
                    'Customer Code'     => '',
                    'Customer Name'     => '',
                    'Salesman Code'     => '',
                    'Salesman Name'     => '',
                    'Latitude'          => '',
                    'Longitude'         => '',
                    'URA Invoice No'    => '',
                    'URA Antifake Code' => '',
                    'URA QR Code'       => '',
                    'Purchaser Name'    => '',
                    'Purchaser Contact' => '',
                    'Invoice Date'      => '',
                    'Invoice Time'      => '',
                    'Invoice Type'      => '',
                    'Gross Total'       => '',
                    'VAT'               => '',
                    'Pre VAT'           => '',
                    'Net Total'         => '',
                    'Promotion Total'   => '',
                    'Discount'          => '',
                    'Total Amount'      => '',
                    'Invoice Number'    => '',
                    // 'Status'            => '',

                    'Item Code'                 => (string)optional($detail->item)->item_code,
                    'Item Name'                 => (string)optional($detail->item)->item_name,
                    'UOM Code'                  => (string)optional($detail->itemuom)->uom_code,
                    'UOM Name'                  => (string)optional($detail->itemuom)->uom_name,
                    'Quantity'                  => (float)$detail->quantity,
                    'Item Value'                => (float)$detail->itemvalue,
                    'VAT (Detail)'              => (float)$detail->vat,
                    'Pre VAT (Detail)'          => (float)$detail->pre_vat,
                    'Net Total (Detail)'        => (float)$detail->net_total,
                    'Item Total'                => (float)$detail->item_total,
                    'Promotion Code'            => (string)optional($detail->promotion)->promotion_code,
                    'Parent'                    => (string)$detail->parent,
                    'Approver Name'             => (string)optional($detail->approver)->name,
                    'Approved Date'             => (string)$detail->approved_date,
                    'Rejected By'               => (string)$detail->rejected_by,
                    'RM Action Date'            => (string)$detail->rmaction_date,
                    'Comment For Rejection'     => (string)$detail->comment_for_rejection,
                    'Detail Status'             => (string)($detail->status ? 'Active' : 'Inactive'),
                ];
            }

            $rows[] = array_fill_keys($this->headings(), '');
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Invoice Code',
            'Currency',
            'Company',
            'Order Code',
            'Delivery Code',
            'Warehouse Code',
            'Warehouse Name',
            'Route Code',
            'Route Name',
            'Customer Code',
            'Customer Name',
            'Salesman Code',
            'Salesman Name',
            'Latitude',
            'Longitude',
            'URA Invoice No',
            'URA Antifake Code',
            'URA QR Code',
            'Purchaser Name',
            'Purchaser Contact',
            'Invoice Date',
            'Invoice Time',
            'Invoice Type',
            'Gross Total',
            'VAT',
            'Pre VAT',
            'Net Total',
            'Promotion Total',
            'Discount',
            'Total Amount',
            'Invoice Number',
            'Status',

            'Item Code',
            'Item Name',
            'UOM Code',
            'UOM Name',
            'Quantity',
            'Item Value',
            'VAT (Detail)',
            'Pre VAT (Detail)',
            'Net Total (Detail)',
            'Item Total',
            'Promotion Code',
            'Parent',
            'Approver Name',
            'Approved Date',
            'Rejected By',
            'RM Action Date',
            'Comment For Rejection',
            'Detail Status',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$last}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
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
                        ],
                    ],
                ]);
            }
        ];
    }
}
