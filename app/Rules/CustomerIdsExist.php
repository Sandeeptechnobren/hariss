<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CustomerNamesExist implements Rule
{
    protected array $matchedCustomerIds = [];

    public function passes($attribute, $value)
    {
        $names = array_map('trim', explode(',', $value));

        $this->matchedCustomerIds = DB::table('tbl_company_customer')
            ->whereNull('deleted_at')
            ->whereNotNull('merchendiser_ids')
            ->whereIn('business_name', $names)
            ->pluck('id', 'business_name')
            ->toArray();

        return count($this->matchedCustomerIds) === count($names);
    }

    public function getMatchedIds(): array
    {
        return $this->matchedCustomerIds;
    }

    public function message()
    {
        return 'One or more business names do not exist or are not valid customers.';
    }
}
