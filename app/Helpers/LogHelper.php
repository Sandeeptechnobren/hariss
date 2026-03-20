<?php

namespace App\Helpers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\DB;


class LogHelper
{
    public static function store(
        string $menuId,
        string $subMenuId,
        string $mode,
        ?array $previousData = null,
        ?array $currentData = null,
        ?int $userId = null
    ): void {
        $agent = new Agent();
        $resolvedUserId = $userId ?? Auth::id();
        $userRoleName = null;

        if ($resolvedUserId) {
            $userRoleName = DB::table('users')
                ->join('roles', 'users.role', '=', 'roles.id')
                ->where('users.id', $resolvedUserId)
                ->value('roles.name');
        }

        Log::create([
            'menu_id'       => $menuId,
            'sub_menu_id'   => $subMenuId,
            'mode'          => $mode,
            'user_id'       => $resolvedUserId,
            'user_role'     => $userRoleName,  

            'previous_data' => $previousData,
            'current_data'  => $currentData,

            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'browser'    => $agent->browser() ?: null,
            'os'         => $agent->platform() ?: null,
        ]);
    }
}
