<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StocksImport implements ToCollection, WithHeadingRow
{
    /**
     * Each row is a row in the sheet (with header columns)
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        // We don't directly save here; instead, we return or store transformed data
        // But with ToCollection, this is how we receive rows.
    }

    /**
     * You might create a helper to map rows to stocks structure
     *
     * @param Collection $rows
     * @return array  // an array of stocks
     */
    public function toStocksArray(Collection $rows)
    {
        // Example: if your sheet has columns:
        // code, activity_name, date_from, date_to, assign_customers, item_id, item_uom, capacity
        //
        // But if you have multiple inventory items per stock, you may need multiple rows per stock
        //
        $grouped = [];

        foreach ($rows as $row) {
            // Assume: assign_customers is CSV string "72,86"
            $code = $row['code'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'code' => $row['code'],
                    'activity_name' => $row['activity_name'],
                    'date_from' => $row['date_from'],
                    'date_to' => $row['date_to'],
                    'assign_customers' => $row['assign_customers']
                                        ? array_map('trim', explode(',', $row['assign_customers']))
                                        : [],
                    'assign_inventory' => [],
                ];
            }

            // For inventory fields
            if (!is_null($row['item_id'])) {
                $grouped[$code]['assign_inventory'][] = [
                    'item_id' => $row['item_id'],
                    'item_uom' => $row['item_uom'],
                    'capacity' => $row['capacity'],
                ];
            }
        }

        // Return as indexed array
        return array_values($grouped);
    }
}