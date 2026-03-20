<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompiledClaimExport implements FromArray, WithHeadings
{
    protected $data;

    private $statusMap = [
        1 => 'Waiting for Agent Approval',
        2 => 'Rejected By Agent',
        3 => 'Waiting for Area Supervisor Approval',
        4 => 'Rejected By Area Supervisor',
        5 => 'Waiting For Regional Manager Approval',
        6 => 'Rejected By Regional Manager',
        7 => 'Awaiting Data Analyst Verification',
        8 => 'Completed',
        9 => 'Rejected',
    ];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            // "Code",
            "Claim Period",
            "Warehouse",
            "Approved Qty (CSE)",
            "Approved Claim Amount",
            "Rejected Qty (CSE)",
            "Rejected Amount",
            "ASM Name",
            "RSM Name",
            "Status",
        ];
    }

    public function array(): array
    {
        return $this->data->map(function ($item) {

            $warehouse = '';

            if (!empty($item->warehouse)) {
                $warehouse = trim(
                    ($item->warehouse->warehouse_code ?? '') .
                        ' - ' .
                        ($item->warehouse->warehouse_name ?? ''),
                    ' -'
                );
            }
            $monthRange = \Carbon\Carbon::parse($item->start_date)->format('j') . '-' .
                \Carbon\Carbon::parse($item->end_date)->format('j') .
                \Carbon\Carbon::parse($item->start_date)->format('F');

            return [
                // $item->osa_code ?? '',
                $monthRange,
                $warehouse,
                $item->approved_qty_cse ?? 0,
                $item->approved_claim_amount ?? 0,
                $item->rejected_qty_cse ?? 0,
                $item->rejected_amount ?? 0,
                $item->asm_name ?? '',

                $item->rsm_name ?? '',
                $this->statusMap[$item->status] ?? 'Unknown',
            ];
        })->toArray();
    }
}
