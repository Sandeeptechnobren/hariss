<?php

namespace App\Exports;

use App\Models\ChillerRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class CRFRequestExport implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * ✅ Same mapping as API
     */
    private function mapStatus($statusId)
    {
        return [
            1  => "Sales Team Requested",
            2  => "IRO Created",
            3  => "IR Created",
            4  => "Requesting for close",
            5  => "Completed",
        ][$statusId] ?? "Unknown";
    }

    /**
     * ✅ FINAL STATUS LOGIC (Same as API)
     */
    private function resolveWorkflowStatus($item): string
    {
        // 🔴 Rejected case
        if (!empty($item->approval_status) && stripos($item->approval_status, 'rejected') !== false) {
            return $item->approval_status;
        }

        // 🟡 No progress
        if (is_null($item->progress)) {
            return $item->approval_status ?? 'Pending Approval';
        }

        $approved = 0;
        $total = 0;

        if (!empty($item->progress) && str_contains($item->progress, '/')) {
            [$approved, $total] = array_map('intval', explode('/', $item->progress));
        }

        // 🟢 Step 1 (initial)
        if ($item->status == 1) {
            if ($approved == 0) {
                return $this->mapStatus(1);
            }
            return $item->approval_status ?? 'Under Approval';
        }

        // 🟢 Completed
        if ($total > 0 && $approved == $total) {
            return $this->mapStatus($item->status) ?? 'Approved';
        }

        // 🟡 Middle flow
        return $item->approval_status ?? 'Pending Approval';
    }

    public function collection()
    {
        $user = auth()->user(); // ✅ ADD THIS
        $filter = $this->filters;
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


        if (!empty($filter)) {

            $warehouseIds = \App\Helpers\CommonLocationFilter::resolveWarehouseIds([
                'company'   => $filter['company_id']   ?? null,
                'region'    => $filter['region_id']    ?? null,
                'area'      => $filter['area_id']      ?? null,
                'warehouse' => $filter['warehouse_id'] ?? null,
                'route'     => $filter['route_id']     ?? null,
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            }
        }
        $query = \App\Helpers\DataAccessHelper::filterWarehouses($query, $user);
        // ✅ SAME AS globalFilterF
        // ✅ STATUS
        $statusInput = $filter['status'] ?? $filter['request_status'] ?? null;

        if (!empty($statusInput)) {
            $statuses = is_array($statusInput)
                ? $statusInput
                : explode(',', $statusInput);

            $statuses = array_filter(array_map('intval', $statuses));

            if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        // ✅ MODEL
        if (!empty($filter['model_id'])) {
            $modelIds = is_array($filter['model_id'])
                ? $filter['model_id']
                : explode(',', $filter['model_id']);

            $query->whereIn('model', array_map('intval', $modelIds));
        }

        // ✅ DATE (IMPORTANT: same parsing)
        if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
            $from = Carbon::parse($filter['from_date'])->startOfDay();
            $to   = Carbon::parse($filter['to_date'])->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        } elseif (!empty($filter['from_date'])) {
            $query->where('created_at', '>=', Carbon::parse($filter['from_date'])->startOfDay());
        } elseif (!empty($filter['to_date'])) {
            $query->where('created_at', '<=', Carbon::parse($filter['to_date'])->endOfDay());
        }

        // dd($query->count());
        // ❌ REMOVE THIS DEBUG
        // dd($query->count());

        return $query->latest()->get()->map(function ($item) {

            $item = \App\Helpers\ApprovalHelper::attach($item, 'Chiller_Request');

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
                optional($fridgeChiller)->osa_code,
                optional($fridgeChiller)->serial_number,
                optional($item->modelNumber)->name,
                optional($brand)->name,
                $item->chiller_size_requested,
                (optional($item->salesman)->osa_code ?? '') . ' - ' . (optional($item->salesman)->name ?? ''),
                (optional($warehouse)->warehouse_code ?? '') . ' - ' . (optional($warehouse)->warehouse_name ?? ''),
                (optional($area)->area_code ?? '') . ' - ' . (optional($area)->area_name ?? ''),
                (optional($region)->region_code ?? '') . ' - ' . (optional($region)->region_name ?? ''),

                $this->resolveWorkflowStatus($item),
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
            'Chiller Code',
            'Serial Number',
            'Model Number',
            'Brand',
            'Chiller Size',
            'Sales Team',
            'Distributor',
            'Area',
            'Region',
            'Status',
        ];
    }

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
