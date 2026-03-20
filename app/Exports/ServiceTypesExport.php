<?php

namespace App\Exports;

use App\Models\ServiceType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServiceTypesExport implements FromCollection, WithHeadings
{
    // Fetch data
    public function collection()
    {
        return ServiceType::all([
            'id',
            'uuid',
            'code',
            'name',
            'status',
            'created_at',
            'updated_at'
        ]);
    }

    // Define CSV headers
    public function headings(): array
    {
        return ['ID', 'UUID', 'Code', 'Name', 'Status', 'Created At', 'Updated At'];
    }
}
