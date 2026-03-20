<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\Request;

class LogService
{
    public function getLogs(Request $request)
    {
        $query = Log::query();

        if ($request->filled('menu_id')) {
            $query->where('menu_id', $request->menu_id);
        }

        if ($request->filled('sub_menu_id')) {
            $query->where('sub_menu_id', $request->sub_menu_id);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }
                
        if ($request->filled('browser')) {
            $query->where('browser', $request->browser);
        }
                        
        if ($request->filled('os')) {
            $query->where('os', $request->os);
        }

        if ($request->filled('user_role')) {
            $query->where('user_role', $request->user_role);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 50);

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getLogById(int $id): ?Log
    {
        return Log::find($id);
    }
}
 