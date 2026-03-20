<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ItemExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $columns;

    protected array $columnMap = [
        'ID'                   => 'id',
        'ERP Code'             => 'erp_code',
        'Code'                 => 'code',
        'Name'                 => 'name',
        'Description'          => 'description',
        'Image'                => 'image',
        'Category Name'        => 'category_name',
        'Sub Category Name'    => 'sub_category_name',
        'Shelf Life'           => 'shelf_life',
        'Brand'                => 'brand',
        'Item Weight'          => 'item_weight',
        'Volume'               => 'volume',
        'Is Promotional'       => 'is_promotional',
        'Is Taxable'           => 'is_taxable',
        'Has Excise'           => 'has_excies',
        'Commodity Goods Code' => 'commodity_goods_code',
        'Excise Duty Code'     => 'excise_duty_code',
        'Base UOM Vol'         => 'base_uom_vol',
        'Alt Base UOM Vol'     => 'alter_base_uom_vol',
        'Distribution Code'    => 'distribution_code',
        'Barcode'              => 'barcode',
        'Net Weight'           => 'net_weight',
        'Tax'                  => 'tax',
        'VAT'                  => 'vat',
        'Excise'               => 'excise',
        'UOM Efris Code'       => 'uom_efris_code',
        'Alt UOM Efris Code'   => 'altuom_efris_code',
        'Item Group'           => 'item_group',
        'Item Group Desc'      => 'item_group_desc',
        'Caps Promo'           => 'caps_promo',
        'Sequence No'          => 'sequence_no',
        'UOM ID'               => 'uom_id',
        'UOM Name'             => 'uom_name',
        'UOM Type'             => 'uom_type',
        'UPC'                  => 'upc',
        'Price'                => 'price',
        'Is Stock Keeping'     => 'is_stock_keeping',
        'Enable For'           => 'enable_for',
        'UOM Status'           => 'uom_status',
        'Keeping Quantity'     => 'keeping_quantity',
        'UOM Ref ID'           => 'uom_ref_id',
        'Status'               => 'status',
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
}
