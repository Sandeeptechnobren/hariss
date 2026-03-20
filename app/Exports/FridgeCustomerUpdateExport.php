<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FridgeCustomerUpdateExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
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
            // $row->id,
            $row->osa_code,
            $row->outlet_name,
            $row->owner_name,
            $row->contact_number,

            $row->customer?->osa_code ?? '',
            $row->customer?->name ?? '',

            $row->salesman?->osa_code ?? '',
            $row->salesman?->name ?? '',

            $row->route?->route_code ?? '',
            $row->route?->route_name ?? '',

            $row->warehouse?->warehouse_code ?? '',
            $row->warehouse?->warehouse_name ?? '',

            $row->brand,
            $row->asset_number,
            $row->serial_no,
            $row->created_at,
            $row->status == 1 ? 'Active' : 'Inactive',
        ];
    }

    public function headings(): array
    {
        return [
            // 'Id',
            'Osa Code',
            'Outlet Name',
            'Owner Name',
            'Contact Number',

            'Customer Code',
            'Customer Name',

            'Salesman Code',
            'Salesman Name',

            'Route Code',
            'Route Name',

            'Warehouse Code',
            'Warehouse Name',

            'Brand',
            'Asset Number',
            'Serial No',
            'Created At',
            'Status',
        ];
    }
}
