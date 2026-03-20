<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentCustomerExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Name',
            'Owner Name',
            'Customer Type',
            'Route Name',
            'Warehouse Name',
            'Outlet Channel',
            'Category',
            'Subcategory',
            'Contact No',
            'Contact No2',
            'WhatsApp No',
            'Street',
            'Town',
            'Landmark',
            'District',
            'Payment Type',
            'Credit Day',
            'VAT No',
            'Credit Limit',
            'Longitude',
            'Latitude',
            'Status',
        ];
    }
}
