<?php

namespace App\Exports;

use App\Models\Agent_Transaction\NewCustomer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class NewCustomerFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $warehouseIds;
    protected $routeIds;
    protected $approval_status;

    public function __construct($fromDate, $toDate, $warehouseIds = [], $routeIds = [], $approval_status = [])
    {
        $this->fromDate     = $fromDate;
        $this->toDate       = $toDate;
        $this->warehouseIds = $warehouseIds;
        $this->routeIds     = $routeIds;
        $this->approval_status     = $approval_status;
    }

    public function collection()
    {
        $rows = [];
        $query = NewCustomer::with([
            'customer',
            'customertype',
            'route',
            'outlet_channel',
            'category',
            'subcategory',
            'getWarehouse'
        ]);

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse', $this->warehouseIds);
        }

        if (!empty($this->routeIds)) {
            $query->whereIn('route_id', $this->routeIds);
        }
        if (!empty($this->approval_status)) {
            $query->whereIn('approval_status', $this->approval_status);
        }
        // dd($query->getWarehouse);
        $customers = $query->get();
        // dd($customers->first()->route, $customers->first()->getWarehouse);
        foreach ($customers as $customer) {
            $rows[] = [
                'Code' => (string) $customer->osa_code,
                'Customer Type' => (string) ($customer->customertype->name ?? ''),
                'Outlet' => (string) $customer->name,
                'Owner' => (string) $customer->owner_name,
                
                'Warehouse' => (string) (
                    ($customer->getWarehouse->warehouse_code ?? '') .
                    ' - ' .
                    ($customer->getWarehouse->warehouse_name ?? '')
                ),
                'Customer' => (string) (
                    ($customer->customer->osa_code ?? '') .
                    ' - ' .
                    ($customer->customer->name ?? '')
                ),

                'Route' => (string) (
                    optional($customer->route)->route_code .
                    ' - ' .
                    optional($customer->route)->route_name
                ),

                'Outlet Channel' => (string) ($customer->outlet_channel->outlet_channel ?? ''),

                'Category' => (string) ($customer->category->customer_category_name ?? ''),

                'Sub Category' => (string) ($customer->subcategory->customer_sub_category_name ?? ''),


                'Landmark' => (string) ($customer->landmark ?? ''),
                'District' => (string) ($customer->district ?? ''),
                'Street' => (string) ($customer->street ?? ''),
                'Town' => (string) ($customer->town ?? ''),

                'WhatsApp No' => (string) ($customer->whatsapp_no ?? ''),
                'Contact No 1' => (string) ($customer->contact_no ?? ''),
                'Contact No 2' => (string) ($customer->contact_no2 ?? ''),

                'Payment Type' => match ((int) ($customer->payment_type ?? 0)) {
                    1 => 'cash',
                    2 => 'cheque',
                    3 => 'transfer',
                    default => '',
                },

                'Credit Days' => (string) ($customer->creditday ?? ''),
                'Credit Limit' => (float) ($customer->credit_limit ?? 0),

                'Latitude' => (string) ($customer->latitude ?? ''),
                'Longitude' => (string) ($customer->longitude ?? ''),

                // 'Approval Status' => (string) ($customer->approval_status ?? ''),
                'Approval Status' => match ((int) ($customer->approval_status ?? 0)) {
                    1 => 'Approved',
                    2 => 'Pending',
                    3 => 'Rejected',
                    default => '',
                },
                'Reject Reason' => (string) ($customer->reject_reason ?? ''),

                // 'Status' => $customer->status == 1 ? 'Active' : 'Inactive',
            ];
        }

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Code',
            'Customer Type',
            'Outlet',
            'Owner',
            'Warehouse',
            'Customer',
            // 'Customer Type Code',
            'Route',
            'Outlet Channel',
            'Category',
            'Sub Category',
            'Landmark',
            'District',
            'Street',
            'Town',
            'WhatsApp No',
            'Contact No 1',
            'Contact No 2',
            'Payment Type',
            'Credit Days',
            'Credit Limit',
            'Latitude',
            'Longitude',
            'Approval Status',
            'Reject Reason',
            // 'Status',
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
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
