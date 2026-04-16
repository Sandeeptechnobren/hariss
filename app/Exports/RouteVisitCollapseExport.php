<?php

// namespace App\Exports;

// use App\Models\RouteVisitHeader;
// use App\Models\Region;
// use App\Models\Area;
// use App\Models\Warehouse;
// use App\Models\Route;
// use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Border;
// use App\Helpers\DataAccessHelper;
// use Illuminate\Support\Facades\Auth;

// class RouteVisitCollapseExport implements
//     FromCollection,
//     WithHeadings,
//     WithEvents,
//     WithStyles,
//     ShouldAutoSize
// {
//     protected $uuid;
//     protected array $groupIndexes = [];

//     protected $regions;
//     protected $areas;
//     protected $warehouses;
//     protected $routes;

//     public function __construct($uuid = null)
//     {
//         $this->uuid = $uuid;

//         // Load master tables once
//         $this->regions    = Region::all()->keyBy('id');
//         $this->areas      = Area::all()->keyBy('id');
//         $this->warehouses = Warehouse::all()->keyBy('id');
//         $this->routes     = Route::all()->keyBy('id');
//     }
//     public function collection()
//     {
//         $rows = [];
//         $rowIndex = 2;

//         $query = RouteVisitHeader::with([
//                 'routeVisits.agentCustomer' // ✅ no filter here
//             ])
//             ->when($this->uuid, fn($q) => $q->where('uuid', $this->uuid))
//             ->orderBy('id', 'desc'); // ✅ no whereHas filter

//         $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

//             foreach ($headers as $header) {

//                 $visits = $header->routeVisits ?? collect();

//                 // ❗ remove skip if empty (optional)
//                 if ($visits->isEmpty()) continue;

//                 $firstVisit = $visits->first();
//                 $headerRow = $rowIndex;

//                 // ========= COMMON DATA ========= 

//                 $regions = collect($firstVisit->region_ids)
//                     ->map(function ($id) {
//                         $r = $this->regions[$id] ?? null;
//                         return $r
//                             ? $r->region_code . ' - ' . $r->region_name
//                             : null;
//                     })
//                     ->filter()
//                     ->implode(', ');

//                 $areas = collect($firstVisit->area_ids)
//                     ->map(function ($id) {
//                         $a = $this->areas[$id] ?? null;
//                         return $a
//                             ? $a->area_code . ' - ' . $a->area_name
//                             : null;
//                     })
//                     ->filter()
//                     ->implode(', ');

//                 $warehouses = collect($firstVisit->warehouse_ids)
//                     ->map(function ($id) {
//                         $w = $this->warehouses[$id] ?? null;
//                         return $w
//                             ? $w->warehouse_code . ' - ' . $w->warehouse_name
//                             : null;
//                     })
//                     ->filter()
//                     ->implode(', ');

//                 $routes = collect($firstVisit->route_ids)
//                     ->map(function ($id) {
//                         $r = $this->routes[$id] ?? null;
//                         return $r
//                             ? $r->route_code . ' - ' . $r->route_name
//                             : null;
//                     })
//                     ->filter()
//                     ->implode(', ');

//                 // ========= HEADER ROW =========

//                 $rows[] = [
//                     $header->osa_code,
//                     optional($header->created_at)->format('d M Y'),
//                     $regions,
//                     $areas,
//                     $warehouses,
//                     $routes,
//                     $visits->count(),
//                 ];
//                 $rowIndex++;

//                 // ========= DETAIL HEADING =========

//                 $detailHeadingRow = $rowIndex;

//                 $rows[] = [
//                     '',
//                     'Customer',
//                     'Customer Type',
//                     'Days',
//                     'From Date',
//                     'To Date',
//                     '',
//                 ];
//                 $rowIndex++;

//                 // ========= DETAIL ROWS =========

//                 foreach ($visits as $visit) {

//                     $customer = optional($visit->agentCustomer);

//                     $customerText = trim(
//                         ($customer->osa_code ?? '') .
//                         ' - ' .
//                         ($customer->name ?? '')
//                     );

//                     $rows[] = [
//                         '',
//                         $customerText,
//                         $visit->customer_type == 1
//                             ? 'Field Customer'
//                             : 'Merchandiser Customer',
//                         implode(', ', $visit->days_list),
//                         optional($visit->from_date)->format('d M Y'),
//                         optional($visit->to_date)->format('d M Y'),
//                         '',
//                     ];

//                     $rowIndex++;
//                 }

//                 if ($detailHeadingRow + 1 < $rowIndex) {
//                     $this->groupIndexes[] = [
//                         'header_row' => $headerRow,
//                         'start' => $detailHeadingRow,
//                         'end' => $rowIndex - 1,
//                     ];
//                 }

//                 $rows[] = array_fill(0, 7, '');
//                 $rowIndex++;
//             }
//         });

//         return new Collection($rows);
//     }

//     public function headings(): array
//     {
//         return [
//             'Header Code',
//             'Created At',
//             'Region',
//             'Area',
//             'Warehouse',
//             'Route',
//             'Visit Count',
//         ];
//     }

//     public function styles(Worksheet $sheet)
//     {
//         // Freeze header
//         $sheet->freezePane('A2');
//     }

//     public function registerEvents(): array
//     {
//         return [
//             AfterSheet::class => function ($event) {

//                 $sheet = $event->sheet->getDelegate();
//                 $lastColumn = $sheet->getHighestColumn();

//                 // ========= MAIN HEADER STYLE =========

//                 $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
//                     'font' => [
//                         'bold' => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                         'size' => 12,
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'], // Dark Blue
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                         ],
//                     ],
//                 ]);

//                 // ========= COLLAPSE LOGIC =========

//                 foreach ($this->groupIndexes as $group) {

//                     for ($i = $group['start']; $i <= $group['end']; $i++) {
//                         $sheet->getRowDimension($i)
//                             ->setOutlineLevel(1)
//                             ->setVisible(false);

//                         $sheet->getStyle("B{$i}")
//                             ->getAlignment()
//                             ->setIndent(1);
//                     }

//                     // Detail heading bold
//                     $sheet->getStyle("B{$group['start']}:F{$group['start']}")
//                         ->getFont()
//                         ->setBold(true);
//                 }

//                 $sheet->setShowSummaryBelow(false);
//             },
//         ];
//     }
// }

namespace App\Exports;

use App\Models\RouteVisitHeader;
use App\Models\Region;
use App\Models\Area;
use App\Models\Warehouse;
use App\Models\Route;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RouteVisitCollapseExport implements
    FromCollection,
    WithHeadings,
    WithEvents,
    WithStyles,
    ShouldAutoSize
{
    protected $uuid;
    protected ?string $searchQuery;
    protected array $groupIndexes = [];

    protected $regions;
    protected $areas;
    protected $warehouses;
    protected $routes;

    public function __construct($uuid = null, ?string $searchQuery = null)
    {
        $this->uuid        = $uuid;
        $this->searchQuery = $searchQuery;

        $this->regions    = Region::all()->keyBy('id');
        $this->areas      = Area::all()->keyBy('id');
        $this->warehouses = Warehouse::all()->keyBy('id');
        $this->routes     = Route::all()->keyBy('id');
    }

    public function collection()
    {
        $rows     = [];
        $rowIndex = 2;

        $query = RouteVisitHeader::with([
                'routeVisits.agentCustomer'
            ])
            ->when($this->uuid, fn($q) => $q->where('uuid', $this->uuid))
            ->orderBy('id', 'desc');

        // Global search — mirrors globalSearch() logic exactly
        if (!empty($this->searchQuery)) {
            $search = $this->searchQuery;

            $query->where(function ($h) use ($search) {
                $h->where('osa_code', 'ILIKE', "%{$search}%");
                $h->orWhereHas('routeVisits', function ($rv) use ($search) {
                    $rv->whereNull('deleted_at')
                        ->where(function ($q) use ($search) {
                            $q->where('osa_code', 'ILIKE', "%{$search}%")
                              ->orWhere('region', 'ILIKE', "%{$search}%")
                              ->orWhere('route', 'ILIKE', "%{$search}%")
                              ->orWhereRaw("from_date::text ILIKE ?", ["%{$search}%"])
                              ->orWhereRaw("to_date::text ILIKE ?", ["%{$search}%"]);

                            $customerTypeMap = [
                                'Field Customer' => '1',
                                'Merchandiser'   => '2',
                            ];
                            $normalizedQuery = strtolower(trim($search));
                            if (isset($customerTypeMap[$normalizedQuery])) {
                                $q->orWhere('customer_type', $customerTypeMap[$normalizedQuery]);
                            }
                        });
                });
            });
        }

        $query->chunk(200, function ($headers) use (&$rows, &$rowIndex) {

            foreach ($headers as $header) {

                $visits = $header->routeVisits ?? collect();

                if ($visits->isEmpty()) continue;

                $firstVisit = $visits->first();
                $headerRow  = $rowIndex;

                $regions = collect($firstVisit->region_ids)
                    ->map(function ($id) {
                        $r = $this->regions[$id] ?? null;
                        return $r ? $r->region_code . ' - ' . $r->region_name : null;
                    })
                    ->filter()->implode(', ');

                $areas = collect($firstVisit->area_ids)
                    ->map(function ($id) {
                        $a = $this->areas[$id] ?? null;
                        return $a ? $a->area_code . ' - ' . $a->area_name : null;
                    })
                    ->filter()->implode(', ');

                $warehouses = collect($firstVisit->warehouse_ids)
                    ->map(function ($id) {
                        $w = $this->warehouses[$id] ?? null;
                        return $w ? $w->warehouse_code . ' - ' . $w->warehouse_name : null;
                    })
                    ->filter()->implode(', ');

                $routes = collect($firstVisit->route_ids)
                    ->map(function ($id) {
                        $r = $this->routes[$id] ?? null;
                        return $r ? $r->route_code . ' - ' . $r->route_name : null;
                    })
                    ->filter()->implode(', ');

                // Header row
                $rows[] = [
                    $header->osa_code,
                    optional($header->created_at)->format('d M Y'),
                    $regions,
                    $areas,
                    $warehouses,
                    $routes,
                    $visits->count(),
                ];
                $rowIndex++;

                // Detail heading row
                $detailHeadingRow = $rowIndex;

                $rows[] = [
                    '',
                    'Customer',
                    'Customer Type',
                    'Days',
                    'From Date',
                    'To Date',
                    '',
                ];
                $rowIndex++;

                // Detail rows
                foreach ($visits as $visit) {
                    $customer     = optional($visit->agentCustomer);
                    $customerText = trim(
                        ($customer->osa_code ?? '') . ' - ' . ($customer->name ?? '')
                    );

                    $rows[] = [
                        '',
                        $customerText,
                        $visit->customer_type == 1 ? 'Field Customer' : 'Merchandiser Customer',
                        implode(', ', $visit->days_list),
                        optional($visit->from_date)->format('d M Y'),
                        optional($visit->to_date)->format('d M Y'),
                        '',
                    ];
                    $rowIndex++;
                }

                if ($detailHeadingRow + 1 < $rowIndex) {
                    $this->groupIndexes[] = [
                        'header_row' => $headerRow,
                        'start'      => $detailHeadingRow,
                        'end'        => $rowIndex - 1,
                    ];
                }

                $rows[] = array_fill(0, 7, '');
                $rowIndex++;
            }
        });

        return new Collection($rows);
    }

    public function headings(): array
    {
        return [
            'Header Code',
            'Created At',
            'Region',
            'Area',
            'Distributor',
            'Route',
            'Visit Count',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet      = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Main header styling
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size'  => 12,
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
                        ],
                    ],
                ]);

                // Collapse logic — completely unchanged
                foreach ($this->groupIndexes as $group) {

                    for ($i = $group['start']; $i <= $group['end']; $i++) {
                        $sheet->getRowDimension($i)
                            ->setOutlineLevel(1)
                            ->setVisible(false);

                        $sheet->getStyle("B{$i}")
                            ->getAlignment()
                            ->setIndent(1);
                    }

                    // Detail heading bold
                    $sheet->getStyle("B{$group['start']}:F{$group['start']}")
                        ->getFont()
                        ->setBold(true);
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }
}

