<?php

namespace App\Exports;

use App\Models\Salesman;
use App\Models\Route;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class SalesmanExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?string $fromDate;
    protected ?string $toDate;
    protected ?string $search;
    protected array $filters;
    protected array $columns;

    public function __construct(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $search = null,
        array $filters = [],
        array $columns = []
    ) {
        $this->fromDate = $fromDate;
        $this->toDate   = $toDate;
        $this->search   = $search;
        $this->filters  = $filters;
        $this->columns  = $columns;
    }

    public function collection(): Collection
    {
        $query = Salesman::with([
        'salesmanType',
        'route',
        'warehouse',
        'subtype'
    ]);

    $query = DataAccessHelper::filterSalesmen($query, Auth::user());
        if ($this->search) {
            $like = '%' . strtolower($this->search) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(CAST(osa_code AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(name AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(contact_no AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(email AS TEXT)) LIKE ?', [$like]);
            });
        }

        foreach ($this->filters as $field => $value) {
            if ($value) {
                $query->where($field, $value);
            }
        }

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    private function columnMap($salesman): array
    {
        return [
            'OSA Code' => $salesman->osa_code,
            'Name' => $salesman->name,
            'Salesman Type Name' => optional($salesman->salesmanType)->salesman_type_name,
            'Project' => optional($salesman->subtype)->name,
            'Designation' => $salesman->designation,
            'Security Code' => $salesman->security_code,
            'Device No' => $salesman->device_no,
            'Route Name' => optional($salesman->route)->route_name,
            'Block Date To' => $salesman->block_date_to,
            'Block Date From' => $salesman->block_date_from,
            'Contact No' => $salesman->contact_no,
            'Distributor Code' => optional($salesman->warehouse)->warehouse_code,
            'Distributor Name' => optional($salesman->warehouse)->warehouse_name,
            'Distributor Owner Name' => optional($salesman->warehouse)->owner_name,
            'SAP ID' => $salesman->sap_id,
            'Is Login' => $salesman->is_login ? 'Yes' : 'No',
            'Email' => $salesman->email,
            'Forceful Login' => $salesman->forceful_login ? 'Yes' : 'No',
            'Is Block' => $salesman->is_block ? 'Yes' : 'No',
            'Reason' => $salesman->reason,
            'Cashier Description Block' => $salesman->cashier_description_block,
            'Invoice Block' => $salesman->invoice_block === null  ? '' : ($salesman->invoice_block == 1 ? 'Yes' : 'No'),
            'Status' => $salesman->status == 1 ? 'Active' : 'Inactive',
        ];
    }

    public function map($salesman): array
    {
        $data = $this->columnMap($salesman);

        if (!empty($this->columns)) {
            return array_values(
                array_intersect_key($data, array_flip($this->columns))
            );
        }

        return array_values($data);
    }

    public function headings(): array
    {
        $all = array_keys($this->columnMap(new Salesman));

        if (!empty($this->columns)) {
            return array_values(array_intersect($all, $this->columns));
        }

        return $all;
    }
}
