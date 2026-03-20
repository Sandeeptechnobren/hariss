<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

// class RouteExport implements FromCollection, WithHeadings
// {
//     protected $routes;

//     public function __construct($routes)
//     {
//         $this->routes = $routes;
//     }

//     public function collection()
//     {
//         return $this->routes->map(function ($route) {
//             return [
//                 $route->route_code,
//                 $route->route_name,
//                 $route->description,
//                 optional($route->warehouse)->warehouse_name,
//                 optional($route->getrouteType)->route_type_name,
//                 optional($route->vehicle)->vehicle_code,
//                 $route->status == 1 ? 'Active' : 'Inactive',
//             ];
//         });
//     }

//     public function headings(): array
//     {
//         return [
//             'Route Code',
//             'Route Name',
//             'Description',
//             'Warehouse',
//             'Route Type',
//             'Vehicle',
//             'Status',
//         ];
//     }
// }
class RouteExport implements FromCollection, WithHeadings
{
    protected $routes;
    protected $columns;

    protected $map = [
        'route_code' => 'Route Code',
        'route_name' => 'Route Name',
        'description' => 'Description',
        'warehouse' => 'Warehouse',
        'route_type' => 'Route Type',
        'vehicle' => 'Vehicle',
        'status' => 'Status',
    ];

    public function __construct($routes, $columns = [])
    {
        $this->routes = $routes;
        $this->columns = $columns ?: array_keys($this->map);
    }

    public function collection()
    {
        return $this->routes->map(function ($route) {
            $row = [];

            foreach ($this->columns as $column) {
                $row[] = match ($column) {
                    'route_code' => $route->route_code,
                    'route_name' => $route->route_name,
                    'description' => $route->description,
                    'warehouse' => optional($route->warehouse)->warehouse_name,
                    'route_type' => optional($route->getrouteType)->route_type_name,
                    'vehicle' => optional($route->vehicle)->vehicle_code,
                    'status' => $route->status == 1 ? 'Active' : 'Inactive',
                    default => '',
                };
            }

            return $row;
        });
    }

    public function headings(): array
    {
        return array_map(fn ($col) => $this->map[$col], $this->columns);
    }
}
