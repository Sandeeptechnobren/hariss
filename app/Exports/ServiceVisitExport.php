<?php

namespace App\Exports;

use App\Models\ServiceVisit;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ServiceVisitExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = ServiceVisit::with('technician')
            ->select([
                'id',
                'osa_code',
                'ticket_type',
                'time_in',
                'time_out',

                'outlet_code',
                'outlet_name',
                'owner_name',
                'contact_no',

                'district',
                'town_village',
                'location',

                'model_no',
                'asset_no',
                'serial_no',
                'branding',

                'is_machine_in_working',
                'cleanliness',
                'condensor_coil_cleand',
                'gaskets',
                'light_working',

                'work_status',
                'complaint_type',
                'comment',

                'technician_id',
                'created_at',
            ])
            ->whereNull('deleted_at');

        // 🔹 Filters

        if (!empty($this->filters['technician_id'])) {

            $technicianIds = is_array($this->filters['technician_id'])
                ? $this->filters['technician_id']
                : array_map('trim', explode(',', $this->filters['technician_id']));

            $query->whereIn('technician_id', $technicianIds);
        }

        if (!empty($this->filters['ticket_type'])) {
            $query->where('ticket_type', $this->filters['ticket_type']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['from_date']);
        }

        if (!empty($this->filters['to_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['to_date']);
        }

        return $query
            ->orderByDesc('id')
            ->get()
            ->map(function ($visit) {
                return [
                    $visit->osa_code,
                    $visit->ticket_type,
                    $visit->time_in,
                    $visit->time_out,
                    $visit->technician
                        ? $visit->technician->osa_code . ' - ' . $visit->technician->name
                        : null,

                    $visit->outlet_code,
                    $visit->outlet_name,
                    $visit->owner_name,
                    $visit->contact_no,

                    $visit->district,
                    $visit->town_village,
                    $visit->location,

                    $visit->model_no,
                    $visit->asset_no,
                    $visit->serial_no,
                    $visit->branding,

                    $visit->is_machine_in_working ? 'Yes' : 'No',
                    $visit->cleanliness ? 'Yes' : 'No',
                    $visit->condensor_coil_cleand ? 'Yes' : 'No',
                    $visit->gaskets ? 'Yes' : 'No',
                    $visit->light_working ? 'Yes' : 'No',

                    $visit->work_status,
                    $visit->complaint_type,
                    $visit->comment,


                    optional($visit->created_at)->toDateString(),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Ticket Type',
            'Time In',
            'Time Out',
            'Technician',

            'Outlet Code',
            'Outlet Name',
            'Owner Name',
            'Contact No',

            'District',
            'Town / Village',
            'Location',

            'Model No',
            'Asset No',
            'Serial No',
            'Branding',

            'Machine Working',
            'Cleanliness',
            'Condenser Coil Cleaned',
            'Gaskets',
            'Light Working',

            'Work Status',
            'Complaint Type',
            'Comment',


            'Created Date',
        ];
    }
}
