<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyCustomerExport implements FromCollection, WithHeadings
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
            'SAP Code',
            'Customer Code',
            'Business Name',
            'Customer Type',
            'Owner Name',
            'Owner No',
            'WhatsApp No',
            'Email',
            'Language',
            'Contact No2',
            'Buyer Type',
            'Road / Street',
            'Town',
            'Landmark',
            'District',
            'Balance',
            'Payment Type',
            'Bank Name',
            'Bank Account Number',
            'Credit Day',
            'TIN No',
            'Accuracy',
            'Credit Limit',
            'Guarantee Name',
            'Guarantee Amount',
            'Guarantee From',
            'Guarantee To',
            'Total Credit Limit',
            'Credit Limit Validity',
            'Region',
            'Area',
            'VAT No',
            'Longitude',
            'Latitude',
            'Threshold Radius',
            'Distribution Channel',
            'Status',
            'Created At',
        ];
    }
}
