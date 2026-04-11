<?php

// namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromQuery;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\FromArray;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\ApprovalHelper;

// class FridgeCustomerUpdateExport implements
//     FromQuery,
//     WithHeadings,
//     WithMapping,
//     ShouldAutoSize,
//     WithEvents
// {
//     protected $query;

//     public function __construct($query)
//     {
//         $this->query = $query;
//     }

//     private function mapStatus($status)
//     {
//         return [
//             1 => "Sales Team Requested",
//             2 => "Closed",
//         ][$status] ?? "Unknown";
//     }

//     private function resolveWorkflowStatus($row): string
//     {
//         if (!empty($row->approval_status) && stripos($row->approval_status, 'rejected') !== false) {
//             return $row->approval_status;
//         }

//         if (is_null($row->progress)) {
//             return $row->approval_status ?? 'Pending Approval';
//         }

//         $approved = 0;
//         $total = 0;

//         if (!empty($row->progress) && str_contains($row->progress, '/')) {
//             [$approved, $total] = array_map('intval', explode('/', $row->progress));
//         }

//         if ($row->status == 1) {

//             if ($approved == 0) {
//                 return $this->mapStatus(1);
//             }

//             return $row->approval_status ?? 'Under Approval';
//         }

//         if ($total > 0 && $approved == $total) {
//             return $this->mapStatus($row->status);
//         }

//         return $row->approval_status ?? 'Pending Approval';
//     }

//     public function query()
//     {
//         return $this->query;
//     }

//     public function map($row): array
//     {
//         $row = ApprovalHelper::attach($row, 'Frige_Customer_Update');

//         return [
//             // $row->id,
//             $row->osa_code,
//             (optional($row->customer)->osa_code ?? '') . ' - ' . (optional($row->customer)->name ?? ''),
//             $row->owner_name,
//             $row->contact_number,
//             (optional($row->outletChannel)->outlet_channel ?? ''),
//             // ✅ Customer (Code - Name)

//             // ✅ Salesman (Code - Name)
//             (optional($row->salesman)->osa_code ?? '') . ' - ' . (optional($row->salesman)->name ?? ''),

//             // ✅ Route (Code - Name)
//             (optional($row->route)->route_code ?? '') . ' - ' . (optional($row->route)->route_name ?? ''),

//             // ✅ Warehouse (Code - Name)
//             (optional($row->warehouse)->warehouse_code ?? '') . ' - ' . (optional($row->warehouse)->warehouse_name ?? ''),


//             $row->brand,
//             $row->asset_number,
//             $row->serial_no,
//             $row->created_at,
//             $this->resolveWorkflowStatus($row)
//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             // 'Id',
//             'Osa Code',
//             'Customer',
//             'Owner Name',
//             'Contact Number',
//             'outlet type',
//             'Salesman',
//             'Route',
//             'Distributor',

//             'Brand',
//             'Chiller Number',
//             'Serial Number',
//             'Created At',
//             'Status',
//         ];
//     }
//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function (AfterSheet $event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => [
//                         'bold'  => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType'   => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                             'color'       => ['rgb' => '000000'],
//                         ],
//                     ],
//                 ]);

//                 $sheet->getRowDimension(1)->setRowHeight(25);
//             },
//         ];
//     }


// namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromQuery;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\ApprovalHelper;

// class FridgeCustomerUpdateExport implements
//     FromQuery,
//     WithHeadings,
//     WithMapping,
//     ShouldAutoSize,
//     WithEvents
// {
//     protected $query;

//     public function __construct($query)
//     {
//         $this->query = $query;
//     }

//     public function query()
//     {
//         return $this->query;
//     }

//     private function mapStatus($status)
//     {
//         return [
//             1 => "Sales Team Requested",
//             2 => "Closed",
//         ][$status] ?? "Unknown";
//     }

//     private function resolveWorkflowStatus($row): string
//     {
//         if (!empty($row->approval_status) && stripos($row->approval_status, 'rejected') !== false) {
//             return $row->approval_status;
//         }

//         if (is_null($row->progress)) {
//             return $row->approval_status ?? 'Pending Approval';
//         }

//         $approved = 0;
//         $total = 0;

//         if (!empty($row->progress) && str_contains($row->progress, '/')) {
//             [$approved, $total] = array_map('intval', explode('/', $row->progress));
//         }

//         if ($row->status == 1) {
//             if ($approved == 0) {
//                 return $this->mapStatus(1);
//             }
//             return $row->approval_status ?? 'Under Approval';
//         }

//         if ($total > 0 && $approved == $total) {
//             return $this->mapStatus($row->status);
//         }

//         return $row->approval_status ?? 'Pending Approval';
//     }

//     public function map($row): array
//     {
//         $row = ApprovalHelper::attach($row, 'Frige_Customer_Update');

//         return [
//             $row->osa_code,

//             // Customer
//             (optional($row->customer)->osa_code ?? '') . ' - ' . (optional($row->customer)->name ?? ''),

//             $row->owner_name,
//             $row->contact_number,

//             // ✅ Outlet Channel (FIXED)
//             optional($row->outletChannel)->outlet_channel ?? '',

//             // Salesman
//             (optional($row->salesman)->osa_code ?? '') . ' - ' . (optional($row->salesman)->name ?? ''),

//             // Route
//             (optional($row->route)->route_code ?? '') . ' - ' . (optional($row->route)->route_name ?? ''),

//             // Distributor / Warehouse
//             (optional($row->warehouse)->warehouse_code ?? '') . ' - ' . (optional($row->warehouse)->warehouse_name ?? ''),

//             $row->brand,
//             $row->asset_number,
//             $row->serial_no,
//             $row->created_at,
//             $this->resolveWorkflowStatus($row)
//         ];
//     }

//     public function headings(): array
//     {
//         return [
//             'Osa Code',
//             'Customer',
//             'Owner Name',
//             'Contact Number',
//             'Outlet Type',
//             'Salesman',
//             'Route',
//             'Distributor',
//             'Brand',
//             'Chiller Number',
//             'Serial Number',
//             'Created At',
//             'Status',
//         ];
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function (AfterSheet $event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 // Header styling
//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => [
//                         'bold'  => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType'   => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                             'color'       => ['rgb' => '000000'],
//                         ],
//                     ],
//                 ]);

//                 $sheet->getRowDimension(1)->setRowHeight(25);
//             },
//         ];
//     }
// }

// <?php

namespace App\Exports;

use App\Models\FrigeCustomerUpdate;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\ApprovalHelper;
use Carbon\Carbon;

class FridgeCustomerUpdateExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    protected $fromDate, $toDate, $warehouseIds, $salesmanIds;

    public function __construct($fromDate, $toDate, $warehouseIds = [], $salesmanIds = [])
    {
        // ✅ Case 1: both empty → current month
        if (empty($fromDate) && empty($toDate)) {
            $this->fromDate = Carbon::now()->startOfMonth()->toDateString();
            $this->toDate   = Carbon::now()->endOfMonth()->toDateString();
        } else {
            // ✅ Case 2: partial handling
            $this->fromDate = $fromDate ?? Carbon::now()->startOfMonth()->toDateString();
            $this->toDate   = $toDate   ?? Carbon::now()->endOfMonth()->toDateString();
        }

        $this->warehouseIds = $warehouseIds;
        $this->salesmanIds  = $salesmanIds;
    }

    // ✅ MAIN QUERY (FILTER BASED)
    public function query()
    {
        $query = FrigeCustomerUpdate::with([
            'salesman:id,osa_code,name',
            'route:id,route_code,route_name',
            'warehouse:id,warehouse_code,warehouse_name',
            'customer:id,osa_code,name',
            'outletChannel:id,outlet_channel'
        ]);

        // ✅ Date filter (always applied)
        $query->whereBetween('created_at', [
            $this->fromDate . ' 00:00:00',
            $this->toDate   . ' 23:59:59'
        ]);

        // ✅ Warehouse filter
        if (!empty($this->warehouseIds)) {
            $query->whereIn('warehouse_id', $this->warehouseIds);
        }

        // ✅ Salesman filter (optional)
        if (!empty($this->salesmanIds)) {
            $query->whereIn('salesman_id', $this->salesmanIds);
        }

        return $query->latest();
    }

    // =========================
    // STATUS LOGIC (UNCHANGED)
    // =========================
    private function mapStatus($status)
    {
        return [
            1 => "Sales Team Requested",
            2 => "Closed",
        ][$status] ?? "Unknown";
    }

    private function resolveWorkflowStatus($row): string
    {
        if (!empty($row->approval_status) && stripos($row->approval_status, 'rejected') !== false) {
            return $row->approval_status;
        }

        if (is_null($row->progress)) {
            return $row->approval_status ?? 'Pending Approval';
        }

        $approved = 0;
        $total = 0;

        if (!empty($row->progress) && str_contains($row->progress, '/')) {
            [$approved, $total] = array_map('intval', explode('/', $row->progress));
        }

        if ($row->status == 1) {
            if ($approved == 0) {
                return $this->mapStatus(1);
            }
            return $row->approval_status ?? 'Under Approval';
        }

        if ($total > 0 && $approved == $total) {
            return $this->mapStatus($row->status);
        }

        return $row->approval_status ?? 'Pending Approval';
    }

    // =========================
    // DATA MAPPING
    // =========================
    public function map($row): array
    {
        $row = ApprovalHelper::attach($row, 'Frige_Customer_Update');

        return [
            $row->osa_code,

            // Customer
            (optional($row->customer)->osa_code ?? '') . ' - ' . (optional($row->customer)->name ?? ''),

            $row->owner_name,
            $row->contact_number,

            // Outlet Type
            optional($row->outletChannel)->outlet_channel ?? '',

            // Salesman
            (optional($row->salesman)->osa_code ?? '') . ' - ' . (optional($row->salesman)->name ?? ''),

            // Route
            (optional($row->route)->route_code ?? '') . ' - ' . (optional($row->route)->route_name ?? ''),

            // Distributor
            (optional($row->warehouse)->warehouse_code ?? '') . ' - ' . (optional($row->warehouse)->warehouse_name ?? ''),

            $row->brand,
            $row->asset_number,
            $row->serial_no,
            $row->created_at?->format('d M Y'),
            $this->resolveWorkflowStatus($row)
        ];
    }

    public function headings(): array
    {
        return [
            'Osa Code',
            'Customer',
            'Owner Name',
            'Contact Number',
            'Outlet Type',
            'Salesman',
            'Route',
            'Distributor',
            'Brand',
            'Chiller Number',
            'Serial Number',
            'Created At',
            'Status',
        ];
    }

    // =========================
    // STYLING (UNCHANGED)
    // =========================
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
