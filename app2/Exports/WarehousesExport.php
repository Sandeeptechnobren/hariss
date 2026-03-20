<?php

namespace App\Exports;

use App\Models\Warehouse;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WarehousesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Warehouse::with(['region', 'area', 'getCompanyCustomer', 'getCompany']);

        foreach ($this->filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, $value);
            }
        }

        return $query->get()->map(function($warehouse) {
            return [
                $warehouse->warehouse_code,
                $warehouse->warehouse_type,
                $warehouse->warehouse_name,
                $warehouse->owner_name,
                $warehouse->owner_number,
                $warehouse->owner_email,
                $warehouse->agreed_stock_capital,
                $warehouse->location,
                $warehouse->city,
                $warehouse->warehouse_manager,
                $warehouse->warehouse_manager_contact,
                $warehouse->tin_no,
                optional($warehouse->getCompany)->company_name,
                $warehouse->warehouse_email,
                optional($warehouse->region)->region_name,
                optional($warehouse->area)->area_name,
                $warehouse->latitude,
                $warehouse->longitude,
                optional($warehouse->getCompanyCustomer)->business_name,
                $warehouse->town_village,
                $warehouse->street,
                $warehouse->landmark,
                $warehouse->is_efris,
                $warehouse->p12_file,
                $warehouse->is_branch,
                $warehouse->status,

            ];
        });
    }

    public function headings(): array
    {
        return [
            'Warehouse Code',
            'Warehouse Type',
            'Warehouse Name',
            'Owner Name',
            'Owner Number',
            'Owner Email',
            'Agreed Stock Capital',
            'Location',
            'City',
            'Warehouse Manager',
            'Manager Contact',
            'TIN No',
            'Company Name', 
            'Warehouse Email',
            'Region Name',
            'Area Name',  
            'Latitude',
            'Longitude',
            'Customer Name', 
            'Town/Village',
            'Street',
            'Landmark',
            'Is EFRIS',
            'P12 File',
            'Is Branch',
            'Status',
        ];
    }
}
