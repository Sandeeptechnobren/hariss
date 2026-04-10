<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Agent_Transaction\AgentTarget;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class TargetCommitionImport implements ToModel, WithValidation, SkipsOnFailure, SkipsOnError, WithMapping
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $skipduplicate;
    protected $map_key_value_array;
    protected $heading_array;
    protected $warehouseMap;

    private $rowsrecords = [];
    private $rows = 0;

    public function __construct($skipduplicate, $map_key_value_array, $heading_array, $warehouseMap)
    {
        $this->skipduplicate = $skipduplicate;
        $this->map_key_value_array = $map_key_value_array;
        $this->heading_array = $heading_array;
        $this->warehouseMap = $warehouseMap;
    }

    public function startRow(): int
    {
        return 2;
    }

    // ✅ MAPPING
    public function map($row): array
    {
        $heading_array = $this->heading_array;

        $ItemCode_key = array_search("Item Code", $heading_array);
        $ItemName_key = array_search("Item Name", $heading_array);
        $Category_key = array_search("Category", $heading_array);
        $Month_key = array_search("Month", $heading_array);
        $Year_key = array_search("Year", $heading_array);

        return [
            'item_code' => $row[$ItemCode_key] ?? "",
            'item_name' => $row[$ItemName_key] ?? "",
            'category'  => $row[$Category_key] ?? "",
            'month'     => $row[$Month_key] ?? "",
            'year'      => $row[$Year_key] ?? "",
            'warehouses' => array_slice($row, $Year_key + 1)
        ];
    }

    // ✅ MAIN LOGIC
    public function model(array $row)
    {
        ++$this->rows;

        // 🔹 ITEM CHECK
        $item = Item::where('erp_code', $row['item_code'])->first();

        if (!$item) {
            return null;
        }

        $month = $row['month'];
        $year  = $row['year'];

        $warehouseValues = $row['warehouses'];
        $warehouseIndex = 0;

        foreach ($this->warehouseMap as $warehouseCode => $warehouseId) {

            $qty = $warehouseValues[$warehouseIndex] ?? 0;
            $qty = trim($qty);

            // ❌ INVALID QTY → THROW ERROR
            if (!is_numeric($qty)) {
                throw new \Exception("Invalid qty '{$qty}' for warehouse '{$warehouseCode}' in item '{$row['item_code']}'");
            }
            // dump($qty);
            $warehouseIndex++;

            if ($qty == '' || $qty == 0) continue;

            $existing = AgentTarget::where([
                'item_id' => $item->id,
                'warehouse_id' => $warehouseId,
                'target_month' => $month,
                'target_year' => $year
            ])->first();

            // 🔴 EXISTS
            if ($existing) {

                if ($this->skipduplicate == 0) {
                    continue;
                }

                if ($this->skipduplicate == 1) {
                    $existing->qty = $qty;
                    $existing->save();
                }
            }
            // 🟢 NEW INSERT
            else {
                AgentTarget::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouseId,
                    'target_month' => $month,
                    'target_year' => $year,
                    'qty' => $qty
                ]);
            }
        }

        $this->rowsrecords[] = $row;
    }

    // ✅ VALIDATION
    public function rules(): array
    {
        return [
            'item_code' => 'required|exists:items,erp_code',
            'item_name' => 'required',
            'category'  => 'required',
            'month'     => 'required',
            'year'      => 'required',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'item_code.required' => 'Item Code required',
            'item_code.exists'   => 'Invalid Item Code',
            'item_name.required' => 'Item Name required',
            'category.required'  => 'Category required',
            'month.required'     => 'Month required',
            'year.required'      => 'Year required',
        ];
    }

    public function successAllRecords()
    {
        return $this->rowsrecords;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }
}
