<?php

// namespace App\Exports;

// use App\Models\ChillerRequest;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;

// class CRFRequestExport implements FromCollection, WithHeadings, ShouldAutoSize
// {
//     protected $filters;

//     public function __construct(array $filters)
//     {
//         $this->filters = $filters;
//     }
//     private $statusMap = [
//         1  => "Sales Team Requested",
//         2  => "Area Sales Manager Accepted",
//         3  => "Area Sales Manager Rejected",
//         4  => "Chiller Officer Accepted",
//         5  => "Chiller Officer Rejected",
//         6  => "Completed",
//         7  => "Chiller Manager Rejected",
//         8  => "Sales/Key Manager Rejected",
//         9  => "Refused by Customer",
//         10 => "Fridge Manager Accepted",
//         11 => "Fridge Manager Rejected",
//     ];
//     private $fridgeStatusMap = [
//         0 => "Not Assigned",
//         1 => "Assigned",
//     ];
//     public function collection()
//     {
//         $query = ChillerRequest::with([
//             'customer',
//             'warehouse.area.region',  
//             'route',         
//             'salesman',
//             'modelNumber',
//             'outlet',
//             'createdBy'
//         ]);

//         if (!empty($this->filters['status'])) {
//             $query->where('status', $this->filters['status']);
//         }

//         foreach (['warehouse_id', 'route_id', 'salesman_id', 'model_id'] as $key) {
//             if (!empty($this->filters[$key])) {
//                 $query->whereIn(
//                     $key === 'model_id' ? 'model' : $key,
//                     explode(',', $this->filters[$key])
//                 );
//             }
//         }

//         if (!empty($this->filters['region_id'])) {
//             $query->whereHas('warehouse.region', function ($q) {
//                 $q->whereIn('id', explode(',', $this->filters['region_id']));
//             });
//         }

//         return $query->latest()->get()->map(function ($item) {

//             $warehouse = $item->warehouse;
//             $area      = optional($warehouse)->area;
//             $region    = optional($area)->region;
//             $route     = $item->route;

//             return [

//                 optional($item->created_at)->format('Y-m-d'),
//                 $item->osa_code,
//                 (optional($item->customer)->osa_code ?? '') . ' - ' . (optional($item->customer)->name ?? ''),
//                 $item->owner_name,
//                 $item->contact_number,
//                 $item->landmark,
//                 // optional($item->customer)->name,
//                 // optional($item->customer)->customer_code,
//                 // optional($item->customer)->city,
//                 optional($item->customer)->district,
//                 // $item->asset_number,
//                 $item->machine_number,
//                 optional($item->modelNumber)->name,
//                 // $item->asset_type,
//                 $item->brand,
//                 $item->chiller_size_requested,
//                 (optional($item->salesman)->osa_code ?? '') . ' - ' . (optional($item->salesman)->name ?? ''),
//                 (optional($warehouse)->warehouse_code ?? '') . ' - ' . (optional($warehouse)->warehouse_name ?? ''),
//                 (optional($area)->area_code ?? '') . ' - ' . (optional($area)->area_name ?? ''),
//                 (optional($region)->region_code ?? '') . ' - ' . (optional($region)->region_name ?? ''),
//                 ($this->fridgeStatusMap[$item->fridge_status] ?? $item->fridge_status),
//                 ($this->statusMap[$item->status] ?? $item->status),
//             ];
//         });
//     }

//     public function headings(): array
//     {
//         return [
//             'Date',
//             'Code',
//             'Customer',
//             'Owner Name',
//             'Contact Number',
//             'Landmark',
//             // 'Customer Code',
//             // 'City',
//             'District',
//             // 'Fridge Code',
//             'Serial Number',
//             'Model Number',
//             // 'Type',
//             'Brand',
//             'Chiller Size',

//             'Salesman',
//             'Warehouse',
//             'Region',
//             'Area',
//             // 'Route',

//             // 'Outlet Name',

//             'Fridge Status',
//             'Status',
//         ];
//     }
// }

namespace App\Exports;

use App\Models\ChillerRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class CRFRequestExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    private $statusMap = [
        1  => "Sales Team Requested",
        2  => "IRO Created",
        3  => "IR Created",
        4  => "Closed",
    ];

    private $fridgeStatusMap = [
        0 => "Not Assigned",
        1 => "Assigned",
    ];

    public function collection()
    {
        $query = ChillerRequest::with([
            'customer',
            'warehouse.area.region',
            'route',
            'salesman',
            'modelNumber',
            'outlet',
            'createdBy',
            'fridgeStatuses.chiller.brand'
        ]);

        $filters = $this->filters;

        // $query->when(
        //     isset($filters['status']),
        //     fn($q) =>
        //     $q->where('status', $filters['status'])
        // );

        $query->when(
            isset($filters['warehouse_id']),
            fn($q) =>
            $q->whereIn('warehouse_id', explode(',', $filters['warehouse_id']))
        );

        $query->when(
            isset($filters['route_id']),
            fn($q) =>
            $q->whereIn('route_id', explode(',', $filters['route_id']))
        );

        $query->when(
            isset($filters['salesman_id']),
            fn($q) =>
            $q->whereIn('salesman_id', explode(',', $filters['salesman_id']))
        );

        $query->when(
            isset($filters['model_id']),
            fn($q) =>
            $q->whereIn('model', explode(',', $filters['model_id']))
        );

        $query->when(isset($filters['region_id']), function ($q) use ($filters) {
            $q->whereHas('warehouse.area.region', function ($sub) use ($filters) {
                $sub->whereIn('id', explode(',', $filters['region_id']));
            });
        });

        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {

            $query->whereBetween('created_at', [
                $filters['from_date'],
                $filters['to_date']
            ]);
        } else {

            // Default Current Month
            $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        }


        $query->when(
            isset($filters['request_status']),
            function ($q) use ($filters) {

                $status = is_array($filters['request_status'])
                    ? $filters['request_status']
                    : explode(',', $filters['request_status']);

                $q->whereIn('status', $status);
            }
        );
        return $query->latest()->get()->map(function ($item) {

            $warehouse = $item->warehouse;
            $area = optional($warehouse)->area;
            $region = optional($area)->region;
            $fridgeChiller = optional(optional($item->fridgeStatuses)->first())->chiller;
            $brand = optional($fridgeChiller)->brand;

            return [
                optional($item->created_at)->format('j M Y'),
                $item->osa_code,
                (optional($item->outlet)->outlet_channel ?? ''),
                (optional($item->customer)->osa_code ?? '') . ' - ' . (optional($item->customer)->name ?? ''),
                $item->owner_name,
                $item->contact_number,
                $item->landmark,
                optional($item->customer)->district,
                optional($item->customer)->town,
                // $item->machine_number,
                optional($fridgeChiller)->osa_code,
                optional($fridgeChiller)->serial_number,
                optional($item->modelNumber)->name,
                optional($brand)->name,
                $item->chiller_size_requested,
                (optional($item->salesman)->osa_code ?? '') . ' - ' . (optional($item->salesman)->name ?? ''),
                (optional($warehouse)->warehouse_code ?? '') . ' - ' . (optional($warehouse)->warehouse_name ?? ''),
                (optional($area)->area_code ?? '') . ' - ' . (optional($area)->area_name ?? ''),
                (optional($region)->region_code ?? '') . ' - ' . (optional($region)->region_name ?? ''),
                // ($this->fridgeStatusMap[$item->fridge_status] ?? $item->fridge_status),
                ($this->statusMap[$item->status] ?? $item->status),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'CRF Code',
            'Outlet Type',
            'Customer',
            'Owner Name',
            'Contact Number',
            'Landmark',
            'District',
            'City',
            'Fridge Code',
            'Serial Number',
            'Model Number',
            'Brand',
            'Chiller Size',
            'Salesman',
            'Warehouse',
            'Area',
            'Region',
            // 'Fridge Status',
            'Status',
        ];
    }
}
