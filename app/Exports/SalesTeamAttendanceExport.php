<?php

namespace App\Exports;

use App\Models\SalesmanAttendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Helpers\DataAccessHelper;

class SalesTeamAttendanceExport implements FromQuery, WithMapping, WithHeadings, WithEvents
{
    use Exportable;

    protected $fromDate;
    protected $toDate;
    protected $salesman_id;

    public function __construct($fromDate = null, $toDate = null, $salesman_id = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->salesman_id = $salesman_id;
    }

    
    public function query()
    {
        $query = SalesmanAttendance::with([
            'salesman',
            'warehouse',
            'route'
        ])
        ->when($this->salesman_id, function ($q) {
            $q->where('salesman_id', $this->salesman_id);
        });

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('attendance_date', [
                $this->fromDate,
                $this->toDate
            ]);
        }
        else {
            $query->whereDate('attendance_date', Carbon::today());
        }

        return $query;
    }

    public function map($row): array
    {
        return [
            optional($row->attendance_date)->format('d M Y'),

            trim(
                ($row->salesman->osa_code ?? '') . ' - ' .
                    ($row->salesman->name ?? '')
            ),

            trim(
                ($row->warehouse->warehouse_code ?? '') . ' - ' .
                    ($row->warehouse->warehouse_name ?? '')
            ),

            trim(
                ($row->route->route_code ?? '') . ' - ' .
                    ($row->route->route_name ?? '')
            ),

            optional($row->time_in)->format('h:i A'),
            optional($row->time_out)->format('h:i A'),

            $row->check_in ? 'Checked In' : 'Not Checked In',
            $row->check_out ? 'Checked Out' : 'Not Checked Out',

            $row->latitude_in,
            $row->longitude_in,

            $row->latitude_out,
            $row->longitude_out,
        ];
    }
    public function headings(): array
    {
        return [
            'Date',
            'Salesman',
            'Distributor',
            'Route',
            'Check-In Time',
            'Check-Out Time',
            'Check-In Status',
            'Check-Out Status',
            'Latitude (In)',
            'Longitude (In)',
            'Latitude (Out)',
            'Longitude (Out)',
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
                        'color' => ['rgb' => 'F5F5F5'],
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
