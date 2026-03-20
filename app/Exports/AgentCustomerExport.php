<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentCustomerExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $columns;

    protected array $availableColumns = [
        'osa_code'       => 'OSA Code',
        'name'           => 'Name',
        'owner_name'     => 'Owner Name',
        'customer_type'  => 'Customer Type',
        'route_name'     => 'Route Name',
        'warehouse_name' => 'Warehouse Name',
        'outlet_channel' => 'Outlet Channel',
        'category'       => 'Category',
        'subcategory'    => 'Subcategory',
        'contact_no'     => 'Contact No',
        'contact_no2'    => 'Contact No2',
        'street'         => 'Street',
        'town'           => 'Town',
        'landmark'       => 'Landmark',
        'district'       => 'District',
        'payment_type'   => 'Payment Type',
        'credit_limit'   => 'Credit Limit',
        'longitude'      => 'Longitude',
        'latitude'       => 'Latitude',
        'status'         => 'Status',
    ];

    public function __construct($data, array $columns = [])
    {
        $this->data = collect($data);

        $this->columns = empty($columns)
            ? array_keys($this->availableColumns)
            : array_values(array_intersect($columns, array_keys($this->availableColumns)));
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            $row = [];

            foreach ($this->columns as $column) {
                $row[] = match ($column) {
                    'osa_code'       => $item->osa_code ?? '',
                    'name'           => $item->name ?? '',
                    'owner_name'     => $item->owner_name ?? '',
                    'customer_type'  => $item->customer_type ?? '',
                    'route_name'     => $item->route_name ?? '',
                    'warehouse_name' => $item->warehouse_name ?? '',
                    'outlet_channel' => $item->outlet_channel ?? '',
                    'category'       => $item->category ?? '',
                    'subcategory'    => $item->subcategory ?? '',
                    'contact_no'     => $item->contact_no ?? '',
                    'contact_no2'    => $item->contact_no2 ?? '',
                    'street'         => $item->street ?? '',
                    'town'           => $item->town ?? '',
                    'landmark'       => $item->landmark ?? '',
                    'district'       => $item->district ?? '',
                    'payment_type'   => match ((int) ($item->payment_type ?? 0)) {
                        1 => 'Cash',
                        2 => 'Cheque',
                        3 => 'Transfer',
                        default => '',
                    },
                    'credit_limit'   => $item->credit_limit ?? '',
                    'longitude'      => $item->longitude ?? '',
                    'latitude'       => $item->latitude ?? '',
                    'status'         => ((int) ($item->status ?? 0) === 1) ? 'Active' : 'Inactive',
                    default          => '',
                };
            }

            return $row;
        });
    }

    public function headings(): array
    {
        return array_map(
            fn ($column) => $this->availableColumns[$column],
            $this->columns
        );
    }
}
