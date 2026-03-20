<?php

namespace App\Exports;

use App\Models\Salesman;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class SalesmanExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?string $fromDate;
    protected ?string $toDate;

    public function __construct(?string $fromDate = null, ?string $toDate = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection(): Collection
    {
        $query = Salesman::with(['salesmanType', 'route', 'warehouse']);

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    public function map($salesman): array
    {
        return [
            $salesman->osa_code,
            $salesman->name,
            optional($salesman->salesmanType)->salesman_type_name,
            $salesman->sub_type,
            $salesman->designation,
            $salesman->security_code,
            $salesman->device_no,
            optional($salesman->route)->route_name,
            $salesman->block_date_to,
            $salesman->block_date_from,
            $salesman->password,
            $salesman->contact_no,
            optional($salesman->warehouse)->warehouse_name,
            optional($salesman->warehouse)->owner_name,
            $salesman->token_no,
            $salesman->sap_id,
            $salesman->is_login,
            $salesman->status,
            $salesman->email,
            $salesman->forceful_login,
            $salesman->is_block,
            $salesman->reason,
            $salesman->cashier_description_block,
            $salesman->invoice_block,
        ];
    }

    public function headings(): array
    {
        return [
            'OSA Code',
            'Name',
            'Salesman Type Name',
            'Sub Type',
            'Designation',
            'Security Code',
            'Device No',
            'Route Name',
            'Block Date To',
            'Block Date From',
            'Password',
            'Contact No',
            'Warehouse Name',
            'Warehouse Owner Name',
            'Token No',
            'SAP ID',
            'Is Login',
            'Status',
            'Email',
            'Forceful Login',
            'Is Block',
            'Reason',
            'Cashier Description Block',
            'Invoice Block'
        ];
    }
}