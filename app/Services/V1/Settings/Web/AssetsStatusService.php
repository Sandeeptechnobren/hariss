<?php
namespace App\Services\V1\Settings\Web;

use App\Models\AssetsStatus;

class AssetsStatusService
{
    public function getDropdownStatuses()
    {
        return AssetsStatus::where('is_active', 1)
            ->orderBy('id')   
            ->select('id', 'name')
            ->get();
    }
}
