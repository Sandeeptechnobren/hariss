<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AsignInventoryExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

     public function array(): array
    {
        // Convert collection to array if it's not already an array
        return $this->data->toArray(); // Convert the Collection to a plain PHP array
    }

     public function headings(): array
    {
        return [
            'Code',
            'Activity Name',
            'Date From',
            'Date To',
            'Item Code',
            'Item UOM',
            'Capacity',
            'Item Name'
        ];
    }
}