<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PetitClaimExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            "id",
            "osa_code",
            "claim_type",
            "warehouse_id",
            "warehouse_code",
            "warehouse_name",
            "petit_name",
            "fuel_amount",
            "rent_amount",
            "agent_amount",
            "month_range",
            "year",
            "status",
            "approver_id",
            "action_date",
            "customercare_id",
            "care_actiondate",
            "care_comment",
            "reject_reason",
            "claim_file"
        ];
    }

    public function array(): array
    {
        return $this->data->map(function ($item) {
            return [
                $item->id,
                $item->osa_code,
                $item->claim_type,
                $item->warehouse_id,
                $item->warehouse->warehouse_code ?? null,
                $item->warehouse->warehouse_name ?? null,
                $item->petit_name,
                $item->fuel_amount,
                $item->rent_amount,
                $item->agent_amount,
                $item->month_range,
                $item->year,
                $item->status,
                $item->approver_id,
                $item->action_date,
                $item->customercare_id,
                $item->care_actiondate,
                $item->care_comment,
                $item->reject_reason,
                $item->claim_file
            ];
        })->toArray();
    }
}
