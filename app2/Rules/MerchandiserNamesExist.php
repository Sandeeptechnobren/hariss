<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class MerchandiserNamesExist implements Rule
{
    protected array $matchedMerchandiserIds = [];

    public function passes($attribute, $value)
    {
        $names = array_map('trim', explode(',', $value));

        $this->matchedMerchandiserIds = DB::table('salesman')
            ->join('salesman_type', 'salesman.type', '=', 'salesman_type.id')
            ->whereNull('salesman.deleted_at')
            ->where('salesman_type.salesman_type_name', 'Merchandiser')
            ->whereIn('salesman.name', $names)
            ->pluck('salesman.id', 'salesman.name')
            ->toArray();

        return count($this->matchedMerchandiserIds) === count($names);
    }

    public function getMatchedIds(): array
    {
        return $this->matchedMerchandiserIds;
    }

    public function message()
    {
        return 'One or more merchandiser names are invalid or not of type Merchandiser.';
    }
}