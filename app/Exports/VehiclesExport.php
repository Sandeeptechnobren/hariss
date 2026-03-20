<?php

// namespace App\Exports;

// use App\Models\Vehicle;
// use Maatwebsite\Excel\Concerns\FromQuery;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;

// class VehiclesExport implements FromQuery, WithHeadings, WithMapping
// {
//     protected $filters;
//     protected $search;

//     public function __construct($filters = [], $search = null)
//     {
//         $this->filters = $filters;
//         $this->search = $search;
//     }

//     public function query()
//     {
//         $query = Vehicle::with([
//             'warehouse:id,warehouse_code,warehouse_name,owner_name',
//             'createdBy:id,name,username',
//             'updatedBy:id,name,username',
//         ]);

//         if (!empty($this->search)) {
//             $likeSearch = '%' . strtolower($this->search) . '%';

//             $query->where(function ($q) use ($likeSearch) {
//                 $q->orWhereRaw('LOWER(vehicle_code) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(number_plat) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(vehicle_chesis_no) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(vehicle_type) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(vehicle_brand) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(description) LIKE ?', [$likeSearch])
//                   ->orWhereRaw('LOWER(capacity) LIKE ?', [$likeSearch]);
//             });
//         }

// foreach ($this->filters as $field => $value) {

//     if ($value !== null && $value !== '') {
//         if (in_array($field, ['vehicle_name', 'vehicle_code'])) {

//             $query->whereRaw(
//                 "LOWER($field) LIKE ?",
//                 ['%' . strtolower($value) . '%']
//             );

//         }
//         elseif ($field == 'status') {

//             $query->where('status', $value);

//         }
//         elseif ($field === 'warehouse_id') {

//             $query->where('warehouse_id', $value);

//         }
//         else {

//             $query->where($field, $value);

//         }
//     }
// }

//         return $query;
//     }

//     public function map($vehicle): array
//     {
//         return [
//             $vehicle->vehicle_code,
//             $vehicle->number_plat,
//             $vehicle->vehicle_chesis_no,
//             optional($vehicle->warehouse)->warehouse_name,
//             $vehicle->description,
//             $vehicle->capacity,
//             $vehicle->vehicle_type,
//             $vehicle->vehicle_brand,
//             $vehicle->owner_type,
//             $vehicle->fuel_reading,
//             $vehicle->valid_from,
//             $vehicle->valid_to,
//             $vehicle->opening_odometer,
//             $vehicle->status == 1 ? 'Active' : 'Inactive',
//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             'Vehicle Code',
//             'Number Plate',
//             'Chessis No',
//             'Warehouse Name',
//             'Description',
//             'Capacity',
//             'Type',
//             'Brand',
//             'Owner Type',
//             'Fuel Reading',
//             'Valid From',
//             'Valid To',
//             'Opening Odometer',
//             'Status',
//         ];
//     }
namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehiclesExport implements FromQuery, WithHeadings, WithMapping
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

    public function map($vehicle): array
    {
        return [
            $vehicle->vehicle_code,
            $vehicle->number_plat,
            $vehicle->vehicle_chesis_no,
            optional($vehicle->warehouse)->warehouse_name,
            $vehicle->description,
            $vehicle->capacity,
            $vehicle->vehicle_type,
            $vehicle->vehicle_brand,
            $vehicle->owner_type,
            $vehicle->fuel_reading,
            $vehicle->valid_from,
            $vehicle->valid_to,
            $vehicle->opening_odometer,
            $vehicle->status == 1 ? 'Active' : 'Inactive',
        ];
    }

    public function headings(): array
    {
        return [
            'Vehicle Code',
            'Number Plate',
            'Chassis No',
            'Distributor Name',
            'Description',
            'Capacity',
            'Type',
            'Brand',
            'Owner Type',
            'Fuel Reading',
            'Valid From',
            'Valid To',
            'Opening Odometer',
            'Status',
        ];
    }
}

