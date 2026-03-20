<?php
namespace App\Services\V1\Assets\Mob;

use App\Models\FridgeTracking ;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetTrackingService
{
public function store($request)
{
    $data = $request->validated();
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $filename = 'fridge_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('assets-tracking', $filename, 'public');

        $data['image'] = '/storage/' . $path;
    }
    if ($request->hasFile('outlet_photo')) {
        $file = $request->file('outlet_photo');
        $filename = 'outlet_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('assets-tracking', $filename, 'public');

        $data['outlet_photo'] = '/storage/' . $path;
    }
    return FridgeTracking::create($data);
}
}