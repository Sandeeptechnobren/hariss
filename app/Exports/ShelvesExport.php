<?php

// namespace App\Exports;

// use App\Models\Shelve;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithEvents;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\Fill;
// use PhpOffice\PhpSpreadsheet\Style\Alignment;
// use PhpOffice\PhpSpreadsheet\Style\Border;

// class ShelvesExport implements FromCollection, WithHeadings, WithEvents
// {
//     public function collection()
//     {
//         return Shelve::select([
//                 'shelf_name',
//                 'code',
//                 'valid_from',
//                 'valid_to',
//                 'height',
//                 'width',
//                 'depth',
//             ])
//             ->latest()
//             ->get()
//             ->map(function ($item) {
//                 return [
//                     'Code'        => $item->code,
//                     'Shelf Name'  => $item->shelf_name,
//                     'Valid From'  => $item->valid_from,
//                     'Valid To'    => $item->valid_to,
//                     'Height'      => $item->height,
//                     'Width'       => $item->width,
//                     'Depth'       => $item->depth,
//                 ];
//             });
//     }

//     public function headings(): array
//     {
//         return [
//             'Shelf Code',
//             'Shelf Name',
//             'Valid From',
//             'Valid To',
//             'Height',
//             'Width',
//             'Depth',
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
//                         'bold' => true,
//                         'color' => ['rgb' => 'FFFFFF'],
//                     ],
//                     'alignment' => [
//                         'horizontal' => Alignment::HORIZONTAL_CENTER,
//                         'vertical'   => Alignment::VERTICAL_CENTER,
//                     ],
//                     'fill' => [
//                         'fillType' => Fill::FILL_SOLID,
//                         'startColor' => ['rgb' => '993442'],
//                     ],
//                     'borders' => [
//                         'allBorders' => [
//                             'borderStyle' => Border::BORDER_THIN,
//                         ],
//                     ],
//                 ]);
//             },
//         ];
//     }
// }
namespace App\Exports;

use App\Models\Shelve;
use App\Models\CompanyCustomer;
use App\Models\Salesman;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ShelvesExport implements FromCollection, WithHeadings, WithEvents
{
    protected ?string $searchTerm;

    public function __construct(?string $searchTerm = null)
    {
        $this->searchTerm = $searchTerm;
    }

    public function collection()
    {
        $query = Shelve::with([
                'createdUser:id,name,username',
                'updatedUser:id,name,username',
                'deletedUser:id,name,username',
            ])
            ->select([
                'shelf_name',
                'code',
                'valid_from',
                'valid_to',
                'height',
                'width',
                'depth',
            ])
            ->latest();

        if (!empty($this->searchTerm)) {
            $like       = '%' . strtolower($this->searchTerm) . '%';
            $searchTerm = $this->searchTerm;

            $query->where(function ($q) use ($like, $searchTerm) {

                $q->orWhereRaw('LOWER(shelf_name) LIKE ?', [$like])
                    ->orWhereRaw('CAST(height AS TEXT) ILIKE ?', [$like])
                    ->orWhereRaw('CAST(width AS TEXT) ILIKE ?', [$like])
                    ->orWhereRaw('CAST(depth AS TEXT) ILIKE ?', [$like])
                    ->orWhereRaw('CAST(id AS TEXT) ILIKE ?', [$like]);

                foreach (['createdUser', 'updatedUser', 'deletedUser'] as $relation) {
                    $q->orWhereHas($relation, fn($sub) =>
                        $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                    );
                }

                $matchingCustomerIds = CompanyCustomer::where(function ($sub) use ($like) {
                    $sub->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(business_name) LIKE ?', [$like]);
                })->pluck('id')->toArray();

                foreach ($matchingCustomerIds as $customerId) {
                    $q->orWhereRaw(
                        '? = ANY(SELECT jsonb_array_elements_text(customer_ids::jsonb))',
                        [(string) $customerId]
                    );
                }

                $matchingMerchIds = Salesman::where(function ($sub) use ($like) {
                    $sub->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
                })->pluck('id')->toArray();

                foreach ($matchingMerchIds as $merchId) {
                    $q->orWhereRaw(
                        '? = ANY(SELECT jsonb_array_elements_text(merchendiser_ids::jsonb))',
                        [(string) $merchId]
                    );
                }

                if (is_numeric($searchTerm)) {
                    $q->orWhereRaw(
                        '? = ANY(SELECT jsonb_array_elements_text(customer_ids::jsonb))',
                        [$searchTerm]
                    );
                    $q->orWhereRaw(
                        '? = ANY(SELECT jsonb_array_elements_text(merchendiser_ids::jsonb))',
                        [$searchTerm]
                    );
                }
            });
        }

        return $query->get()->map(function ($item) {
            return [
                'Code'       => $item->code,
                'Shelf Name' => $item->shelf_name,
                'Valid From' => $item->valid_from,
                'Valid To'   => $item->valid_to,
                'Height'     => $item->height,
                'Width'      => $item->width,
                'Depth'      => $item->depth,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Shelf Code',
            'Shelf Name',
            'Valid From',
            'Valid To',
            'Height',
            'Width',
            'Depth',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet      = $event->sheet->getDelegate();
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
                        ],
                    ],
                ]);
            },
        ];
    }
}