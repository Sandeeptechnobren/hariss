<?php

namespace App\Exports;

use App\Models\CompanyCustomer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Helpers\DataAccessHelper;
use Illuminate\Support\Facades\Auth;

class CompanyCustomerExport implements FromCollection, WithHeadings, WithMapping
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

    /* ---------------- COLLECTION ---------------- */
    public function collection(): Collection
    {
        $query = CompanyCustomer::with([
            'getRegion',
            'getArea',
            'getOutletChannel',
            'companyType'
        ]);

        // ✅ APPLY HIERARCHY FILTER HERE
        $query = DataAccessHelper::filterCompanyCustomers($query, Auth::user());

        /* ---- SEARCH ---- */
        if ($this->search) {
            $like = '%' . strtolower($this->search) . '%';

            $query->where(function ($q) use ($like) {

                $q->orWhereRaw('LOWER(CAST(osa_code AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(sap_code AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(business_name AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(language AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(town AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(landmark AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(district AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(payment_type AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(tin_no AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(bank_guarantee_name AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(bank_guarantee_from AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(bank_guarantee_to AS TEXT)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(CAST(contact_number AS TEXT)) LIKE ?', [$like])

                  ->orWhereRaw('creditday::text LIKE ?', [$like])
                  ->orWhereRaw('creditlimit::text LIKE ?', [$like])
                  ->orWhereRaw('totalcreditlimit::text LIKE ?', [$like])
                  ->orWhereRaw('status::text LIKE ?', [$like]);

                $q->orWhereHas('getRegion', fn($r) =>
                    $r->whereRaw('LOWER(region_name) LIKE ?', [$like])
                );

                $q->orWhereHas('getArea', fn($a) =>
                    $a->whereRaw('LOWER(area_name) LIKE ?', [$like])
                );

                $q->orWhereHas('getOutletChannel', fn($c) =>
                    $c->whereRaw('LOWER(outlet_channel) LIKE ?', [$like])
                );

                $q->orWhereHas('companyType', fn($t) =>
                    $t->whereRaw('LOWER(name) LIKE ?', [$like])
                );
            });
        }

        /* ---- FILTERS ---- */
        foreach ($this->filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        /* ---- DATE FILTER ---- */
        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query->get();
    }

    /* ---------------- COLUMN MAP ---------------- */
    private function columnMap($c): array
    {
        return [
            'SAP Code' => $c->sap_code,
            'OSA Code' => $c->osa_code,
            'Business Name' => $c->business_name,
            'Language' => $c->language,
            'Town' => $c->town,
            'Landmark' => $c->landmark,
            'District' => $c->district,
            'Payment Type' => $c->payment_type,
            'Credit Days' => $c->creditday,
            'TIN No' => $c->tin_no,
            'Credit Limit' => $c->creditlimit,
            'Bank Guarantee Name' => $c->bank_guarantee_name,
            'Bank Guarantee Amount' => $c->bank_guarantee_amount,
            'Bank Guarantee From' => $c->bank_guarantee_from,
            'Bank Guarantee To' => $c->bank_guarantee_to,
            'Total Credit Limit' => $c->totalcreditlimit,
            'Credit Limit Validity' => $c->credit_limit_validity,
            'Region' => optional($c->getRegion)->region_name,
            'Area' => optional($c->getArea)->area_name,
            'Distribution Channel' => optional($c->getOutletChannel)->outlet_channel,
            'Contact Number' => $c->contact_number,
            'Company Type' => optional($c->companyType)->name,
            'Status' => $c->status == 1 ? 'Active' : 'Inactive',
        ];
    }

    /* ---------------- MAPPING ---------------- */
    public function map($row): array
    {
        $data = $this->columnMap($row);

        if (!empty($this->columns)) {
            return array_values(
                array_intersect_key($data, array_flip($this->columns))
            );
        }

        return array_values($data);
    }

    /* ---------------- HEADINGS ---------------- */
    public function headings(): array
    {
        $all = array_keys($this->columnMap(new CompanyCustomer));

        if (!empty($this->columns)) {
            return array_values(array_intersect($all, $this->columns));
        }

        return $all;
    }
}
