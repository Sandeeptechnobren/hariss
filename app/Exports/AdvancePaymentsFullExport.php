<?php

namespace App\Exports;

use App\Models\Agent_Transaction\AdvancePayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class AdvancePaymentsFullExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $uuid;

    public function __construct($uuid = null)
    {
        $this->uuid = $uuid;
    }

    public function collection()
{
    $rows = [];

    $query = AdvancePayment::with(['companyBank', 'agent']);

    if ($this->uuid) {
        $query->where('uuid', $this->uuid); 
    }

    $query = DataAccessHelper::filterAgentTransaction($query, Auth::user());

    $payments = $query->get();

    foreach ($payments as $payment) {
        $rows[] = [
            (string)($payment->osa_code ?? ''),
            match($payment->payment_type) {
                1 => 'Cash',
                2 => 'Cheque',
                3 => 'Transfer',
                default => '-',
            },
            (string)($payment->companyBank->bank_name ?? ''),
            (string)($payment->companyBank->account_number ?? ''),
            (string)($payment->companyBank->branch ?? ''),
            (string)($payment->agent->bank_name ?? ''),
            (string)($payment->agent->bank_account_number ?? ''),
            (float)($payment->amount ?? 0),
            (string)($payment->recipt_no ?? ''),
            $payment->recipt_date ? \Carbon\Carbon::parse($payment->recipt_date)->format('Y-m-d') : '',
            (string)($payment->cheque_no ?? ''),
            $payment->cheque_date ? \Carbon\Carbon::parse($payment->cheque_date)->format('Y-m-d') : '',
            (string)($payment->status == 1 ? 'Active' : 'Inactive'),
        ];
    }

    return new Collection($rows);
}
    /**
     * Define the Excel column headings.
     */
    public function headings(): array
    {
        return [
            'OSA Code',
            'Payment Type',
            'Company Bank Name',
            'Company Account No',
            'Company Branch',
            'Agent Bank Name',
            'Agent Account Number',
            'Amount',
            'Receipt No',
            'Receipt Date',
            'Cheque No',
            'Cheque Date',
            'Receipt Image',
            'Status',
        ];
    }

    /**
     * Style the Excel sheet after creation.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                // Style header row
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '993442'], // Burgundy red
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
