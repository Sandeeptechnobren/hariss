<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ItemExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $data;
    protected $columns;

    protected array $columnMap = [
        'ERP Code'             => 'erp_code',
        'Code'                 => 'code',
        'Name'                 => 'name',
        'Category Name'        => 'category_name',
        'Sub Category Name'    => 'sub_category_name',
        'Shelf Life'           => 'shelf_life',
        'Brand'                => 'brand',
        'Item Weight'          => 'item_weight',
        'Volume'               => 'volume',
        'Is Taxable'           => 'is_taxable',
        'Has Excise'           => 'has_excies',
        'Commodity Goods Code' => 'commodity_goods_code',
        'Excise Duty Code'     => 'excise_duty_code',
        'UOM Name'             => 'uom_name',
        'UPC'                  => 'upc',
        'Price'                => 'price',
        'Is Stock Keeping'     => 'is_stock_keeping',
        'Enable For'           => 'enable_for',
    ];

    public function __construct($data, array $columns = [])
    {
        $this->data = collect($data);

        $this->columns = empty($columns)
            ? array_keys($this->columnMap)
            : array_values(array_intersect($columns, array_keys($this->columnMap)));
    }

    public function collection()
    {
        return $this->data;
    }

    public function map($row): array
    {
        $mapped = [];

        foreach ($this->columns as $label) {
            $key = $this->columnMap[$label];
            $mapped[] = $row[$key] ?? '';
        }

        return $mapped;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function ($event) {

            $columnCount = count($this->headings());
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);

            $headerRange = "A1:{$lastColumn}1";

            $event->sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => '993442', // ✅ maroon
                    ],
                ],
            ]);
        },
    ];
}
}
