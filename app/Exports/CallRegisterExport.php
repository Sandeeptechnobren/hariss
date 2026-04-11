<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class CallRegisterExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
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
            $row->osa_code,
            $row->ticket_type,
            $row->ticket_date
                ? Carbon::parse($row->ticket_date)->format('d M Y')
                : '',


            trim(
                ($row->technician?->osa_code ?? '') . ' - ' .
                    ($row->technician?->name ?? ''),
                ' -'
            ),

            $row->asset?->osa_code ?? '',
            $row->asset?->serial_number ?? '',
            $row->asset?->modelNumber?->code ?? '',
            $row->asset?->modelNumber?->name ?? '',
            $row->asset?->manufacture?->name ?? '',
            $row->asset?->brand?->name ?? '',
            $row->asset?->country?->country_name ?? '',

            trim(
                ($row->asset?->customer?->osa_code ?? '') . ' - ' .
                    ($row->asset?->customer?->name ?? ''),
                ' -'
            ),

            $row->asset?->customer?->owner_name ?? '',
            $row->asset?->customer?->town ?? '',
            $row->asset?->customer?->district ?? '',
            $row->asset?->customer?->contact_no ?? '',
            $row->asset?->customer?->contact_no2 ?? '',

            $row->nature_of_call,
            $row->created_at
                ? Carbon::parse($row->created_at)->format('d M Y')
                : '',

            // ✅ Completion Date
            $row->completion_date
                ? Carbon::parse($row->completion_date)->format('d M Y')
                : '',
            $row->status,
        ];
    }

    public function headings(): array
    {
        return [
            'Ticket Number',
            'Ticket Type',
            'Ticket Date',
            'Technician',
            'Chiller Code',
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
            'Call Status',
        ];
    }

    // 🔥 HEADER STYLE
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Header range (Row 1)
                $headerRange = "A1:{$lastColumn}1";

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'], // white text
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => '993442', // 🔹 green background (change if needed)
                        ],
                    ],
                ]);
            },
        ];
    }
}
