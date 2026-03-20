<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\AssetTracking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssetTrackingService
{

    public function getByUuid(string $uuid): ?AssetTracking
    {
        return AssetTracking::where('uuid', $uuid)
        ->where('created_user', Auth::id())
        ->first();
    }

    public function getAll()
    {
      return AssetTracking::where('created_user', Auth::id())
      ->latest()
      ->paginate(10);
    }


}