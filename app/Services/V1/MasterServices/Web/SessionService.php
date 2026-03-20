<?php
namespace App\Services\V1\MasterServices\Web;

use App\Models\LoginSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
class SessionService
{
public function createSession($user, $tokenResult, Request $request)
{
    $maxSessions = config('auth.max_login_sessions', 10000);
    $activeSessions = LoginSession::where('user_id', $user->id)->count();

    if ($activeSessions >= $maxSessions) {
        throw new HttpException(
            429,
            "Login limit reached. You can only login from {$maxSessions} devices."
        );
    }
    return LoginSession::create([
        'user_id'     => $user->id,
        'token_id'    => $tokenResult->token->id,
        'device'      => $request->input('device', 'Unknown'),
        'ip_address'  => $request->ip(),
        'user_agent'  => $request->userAgent(), 
        'last_used_at'=> now(),
    ]);
}
    public function updateSessionActivity(Request $request)
    {
        if (Auth::check() && $request->user()) {
            $tokenId = $request->user()->token()->id;

            LoginSession::where('token_id', $tokenId)->update([
                'last_used_at' => now(),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
            ]);
        }
    }
    public function getUserSessions($userId)
    {
        return LoginSession::where('user_id', $userId)->get();
    }
    public function deleteSession($tokenId)
    {
        LoginSession::where('token_id', $tokenId)->delete();
    }
    public function deleteAllSessions($userId)
    {
        LoginSession::where('user_id', $userId)->delete();
    }
}
