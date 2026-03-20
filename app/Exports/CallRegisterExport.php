<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CallRegisterExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function map($row): array
    {
        return [
            // 🔹 CALL DETAILS
            $row->osa_code,
            $row->ticket_type,
            $row->ticket_date,

            // 🔹 TECHNICIAN (CODE + NAME)
            trim(
                ($row->technician?->osa_code ?? '') .
                    ' - ' .
                    ($row->technician?->name ?? ''),
                ' -'
            ),

            // 🔹 ASSET
            $row->asset?->osa_code ?? '',
            $row->asset?->serial_number ?? '',
            $row->asset?->modelNumber?->code ?? '',
            $row->asset?->modelNumber?->name ?? '',
            $row->asset?->manufacture?->name ?? '',
            $row->asset?->brand?->name ?? '',
            $row->asset?->country?->country_name ?? '',

            // 🔹 CUSTOMER (CODE + NAME)
            trim(
                ($row->asset?->customer?->osa_code ?? '') .
                    ' - ' .
                    ($row->asset?->customer?->name ?? ''),
                ' -'
            ),

            // 🔹 CUSTOMER DETAILS
            $row->asset?->customer?->owner_name ?? '',
            $row->asset?->customer?->town ?? '',
            $row->asset?->customer?->district ?? '',
            $row->asset?->customer?->contact_no ?? '',
            $row->asset?->customer?->contact_no2 ?? '',

            // 🔹 META
            $row->nature_of_call,
            $row->created_at,
            $row->completion_date,
            $row->status,
        ];
    }


    public function headings(): array
    {
        return [
            'OSA Code',
            'Ticket Type',
            'Ticket Date',

            'Technician',

            'Asset Code',
            'Serial Number',
            'Model Code',
            'Model Name',
            'Manufacturer',
            'Brand',
            'Country',

            'Customer',
            'Owner Name',
            'Town',
            'District',
            'Contact No 1',
            'Contact No 2',

            'Nature Of Call',
            'Created At',
            'Completion Date',
            'Status',
        ];
    }
}
