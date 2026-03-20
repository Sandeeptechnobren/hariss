<?php

namespace App\Exports;

use App\Models\CampaignInformation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class CampaignInformationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = CampaignInformation::with(['merchandiser', 'customer']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        return $query->get()->map(function ($item) {
            return [
                'code' => $item->code,
                'merchandiser' => $item->merchandiser->name ?? '',
                'customer' => $item->customer->business_name ?? '',
                'feedback' => $item->feedback,
                'images' => json_encode($item->images),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Merchandiser Name',
            'Customer Business Name',
            'Feedback',
            'Images',
            // 'Created At'
        ];
    }
}