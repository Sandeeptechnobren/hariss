<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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
class RouteExport implements FromCollection, WithHeadings, WithEvents
{
    protected $routes;
    protected $columns;

    protected $map = [
        'route_code' => 'Route Code',
        'route_name' => 'Route Name',
        'description' => 'Description',
        'warehouse' => 'Distributor',
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
                    //'warehouse' => optional($route->warehouse)->warehouse_name,
                    // 'warehouse' => $route->warehouse
                    //               ? $route->warehouse->warehouse_code . ' - ' . $route->warehouse->warehouse_name
                    //              : null,
                    'warehouse' => $route->warehouse
    ? trim(($route->warehouse->warehouse_code ?? '') . ' - ' . $route->warehouse->warehouse_name, ' -')
    : null,
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

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();
            $lastColumn = $sheet->getHighestColumn();

            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'], // white text
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFDC3545', // 🔥 proper red (same as your image)
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

            // Row height
            $sheet->getRowDimension(1)->setRowHeight(25);
        },
    ];
}
}
