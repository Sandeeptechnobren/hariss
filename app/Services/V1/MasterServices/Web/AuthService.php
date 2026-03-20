<?php

namespace App\Services\V1\MasterServices\Web;

use App\Http\Resources\V1\Master\Web\UserResource;
use App\Models\User;
use App\Models\ReportKey;
use App\Models\Login_alert\LoginSecurityLog;
use App\Models\LoginSession;
use App\Notifications\UnauthorizedLoginDetected;
use App\Mail\LoginAttemptMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthService
{
    public function register(array $payload): array
    {
        $user_id = Auth::user()->id;
        $existing = User::where('email', $payload['email'])
            ->orWhere('username', $payload['email'])
            ->orWhere('email', $payload['username'])
            ->orWhere('username', $payload['username'])
            ->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['Email or Username already exists.'],
                'username' => ['Email or Username already exists.'],
            ]);
        }
        foreach (['company', 'warehouse', 'route', 'region', 'area', 'outlet_channel'] as $field) {
            if (!isset($payload[$field]) || is_null($payload[$field]) || $payload[$field] === '' || $payload[$field] === '?') {
                $payload[$field] = null;
            } else {
                $payload[$field] = is_array($payload[$field]) ? $payload[$field] : [$payload[$field]];
            }
        }
        $user = User::create([
            'name'            => $payload['name'] ?? null,
            'email'           => $payload['email'] ?? null,
            'username'        => $payload['username'] ?? null,
            'contact_number'  => $payload['contact_number'],
            'password'        => Hash::make($payload['password']),
            'profile_picture' => $payload['profile_picture'] ?? null,
            'role'            => $payload['role'] ?? 0,
            'status'          => $payload['status'] ?? 1,
            'street'          => $payload['street'] ?? null,
            'city'            => $payload['city'] ?? null,
            'zip'             => $payload['zip'] ?? null,
            'dob'             => $payload['dob'] ?? null,
            'country_id'      => $payload['country_id'] ?? null,
            'company'         => $payload['company'] ?? null,
            'warehouse'       => $payload['warehouse'] ?? null,
            'route'           => $payload['route'] ?? null,
            'salesman'        => $payload['salesman'] ?? null,
            'region'          => $payload['region'] ?? null,
            'area'            => $payload['area'] ?? null,
            'outlet_channel'  => $payload['outlet_channel'] ?? null,
            'created_by'      => $user_id,
            'updated_user'    => $payload['updated_user'] ?? 0,
            'Created_Date'    => $payload['Created_Date'] ?? now(),
        ]);
        $tokenResult = $user->createToken('api-token');
        return [
            'user'         => new UserResource($user),
            'token_type'   => 'Bearer',
            'access_token' => $tokenResult->accessToken,
        ];
    }
    public function login(array $payload): array
    {
        DB::beginTransaction();
        try {
            $credentials = [
                filter_var($payload['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username'
                => $payload['email'],
                'password' => $payload['password']
            ];
            if (!Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $user = Auth::user()->load('roleDetails:id,name');
            $tokenResult = $user->createToken('api-token');
            // $reportToken= generate($user->id);

            $user->update(['Login_Date' => now()]);
            DB::commit();
            return [
                'user'         => new UserResource($user),
                'token_type'   => 'Bearer',
                'access_token' => $tokenResult->accessToken,
                'expires'      => $tokenResult->token->expires_at,
                'tokenResult'  => $tokenResult,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function me(User $id): UserResource
    {
        return new UserResource($id);
    }
    public function logout(User $user): void
    {
        $user->token()->revoke();
    }
    public function checkToken()
    {
        $user = Auth::user();
        $token = $user?->token();
        if (!$user || !$token) {
            return false;
        }
        return LoginSession::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->exists();
    }
    public function getUserList(int $perPage = 50)
    {
        $user_id = Auth::user()->id;

        // $users = User::with('roleDetails:id,name','getCompanyDetails:id')
        //     // ->where('created_by',$user_id)
        //     ->orderBy('created_at', 'desc')
        //     ->paginate($perPage);   // ✅ Only this line changed
        $users = User::with(
            'roleDetails:id,name'
            // 'getCompanyDetails:id,company_code,company_name'
        )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $users;
    }

    public function updateUser($uuid, array $payload): array
    {
        // dd($payload);
        $user = User::where('uuid', $uuid)->firstorFail();
        foreach (['company', 'warehouse', 'route', 'region', 'area', 'outlet_channel'] as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = is_array($payload[$field]) ? $payload[$field] : [$payload[$field]];
            }
        }
        if (isset($payload['password'])) {
            $payload['password'] = \Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }
        // dd($payload);
        $user->update($payload);
        return [
            'user' => new \App\Http\Resources\V1\Master\Web\UserResource($user),
        ];
    }


    public function getUserbyUuid($uuid)
    {
        $user = User::where('uuid', $uuid)->firstorFail();
        return [
            'user' => new \App\Http\Resources\V1\Master\Web\UserResource($user),
        ];
    }

    public function checkUser(string $query): bool
    {
        return User::where(function ($q) use ($query) {
            $q->where('email', $query)
                ->orWhere('username', $query);
        })->exists();
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): array
    {
        // 🔐 Check old password
        if (!Hash::check($oldPassword, $user->password)) {
            return [
                'status'  => 'error',
                'message' => 'Old password does not match',
            ];
        }

        // 🔁 Update password
        $user->update([
            'password'      => Hash::make($newPassword),
            'updated_user'  => $user->id,
            'Modifier_Id'   => $user->id,
            'Modifier_Name' => $user->name,
            'Modifier_Date' => now(),
        ]);

        return [
            'status'  => 'success',
            'message' => 'Password changed successfully',
        ];
    }


    // public function loginalert(array $payload): array
    //     {
    //         $loginField = filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
    //             ? 'email'
    //             : 'username';

    //         $user = User::where($loginField, $payload['email'])->first();

    //         if ($user && $user->is_block) {
    //             throw ValidationException::withMessages([
    //                 'email' => ['Please contact to Admin.']
    //             ]);
    //         }

    //         $credentials = [
    //             $loginField => $payload['email'],
    //             'password'  => $payload['password']
    //         ];

    //         if (!Auth::attempt($credentials)) {

    //             $log = $this->handleFailedLogin($payload, $user);

    //             if ($log->attempt_count >= 3 && $user) {

    //                 $this->blockUser($user);

    //                 throw ValidationException::withMessages([
    //                     'email' => ['Please contact to Admin.']
    //                 ]);
    //             }

    //             throw ValidationException::withMessages([
    //                 'email' => ['The provided credentials are incorrect.'],
    //             ]);
    //         }
    //         $this->resetLoginAttempts($payload['email']);

    //         DB::beginTransaction();

    //         try {

    //             $user = Auth::user()->load('roleDetails:id,name');

    //             $tokenResult = $user->createToken('api-token');
    //             $user->update(['Login_Date' => now()]);

    //             DB::commit();

    //             return [
    //                 'user'         => $user,
    //                 'token_type'   => 'Bearer',
    //                 'access_token' => $tokenResult->accessToken,
    //                 'expires'      => $tokenResult->token->expires_at,
    //                 'tokenResult'  => $tokenResult,
    //             ];
    //         } catch (\Throwable $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    //     }


    public function loginalert(array $payload): array
    {
        $loginField = filter_var($payload['email'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';
        $user = User::where($loginField, $payload['email'])->first();
        if ($user && $user->is_block) {
            throw ValidationException::withMessages([
                'email' => ['Please contact to Admin.']
            ]);
        }
        $credentials = [
            $loginField => $payload['email'],
            'password'  => $payload['password']
        ];
        if (!Auth::attempt($credentials)) {
            $log = $this->handleFailedLogin($payload, $user);
            if ($log->attempt_count >= 3 && $user) {
                $this->blockUser($user);
                throw ValidationException::withMessages([
                    'email' => ['Please contact to Admin.']
                ]);
            }
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }
        $this->resetLoginAttempts($payload['email']);
        DB::beginTransaction();
        try {
            $user = Auth::user()->load('roleDetails:id,name');
            $tokenResult = $user->createToken('api-token');
            // $reportToken = $this->generateUniqueReportKey($user->id);
            // $pass = ReportKey::updateOrCreate(
            //     ['user_id' => $user->id],
            //     [
            //         'api_key'   => $reportToken,
            //         'is_active' => true,
            //     ]
            // );
            $reportKey = ReportKey::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'api_key'   => $this->generateUniqueReportKey($user->id),
                    'is_active' => true,
                ]
            );
            $reportToken = $reportKey->api_key;
            $user->update([
                'Login_Date' => now()
            ]);
            DB::commit();
            return [
                'user'         => $user,
                'token_type'   => 'Bearer',
                'access_token' => $tokenResult->accessToken,
                'expires'      => $tokenResult->token->expires_at,
                'tokenResult'  => $tokenResult,
                'reportToken'  => $reportToken,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    private function generateUniqueReportKey(int $userId): string
    {
        do {
            $token = 'reportkey_' . $userId . '_' . Str::random(40);
        } while (
            ReportKey::where('api_key', $token)->exists()
        );
        return $token;
    }

    public function validate(string $key): ?ReportKey
    {
        return ReportKey::where('api_key', $key)
            ->where('is_active', true)
            ->first();
    }


    private function blockUser($user)
    {
        $user->update([
            'is_block' => true,
            'updated_at' => now()
        ]);
    }

    private function resetLoginAttempts($email)
    {
        LoginSecurityLog::where('email', $email)
            ->update(['attempt_count' => 0]);
    }

    private function handleFailedLogin(array $payload, $user = null)
    {
        $ip    = request()->ip();
        $agent = request()->header('User-Agent');

        $log = LoginSecurityLog::where('email', $payload['email'])
            ->whereDate('attempt_time', now()->toDateString())
            ->first();

        if ($log) {
            $log->increment('attempt_count');
            $log->refresh();
        } else {
            $log = LoginSecurityLog::create([
                'user_id'       => $user->id ?? null,
                'username'      => $user->username ?? null,
                'email'         => $payload['email'],
                'password_hash' => hash('sha256', $payload['password']),
                'ip_address'    => $ip,
                'device'        => $agent,
                'location'      => null,
                'attempt_time'  => now(),
                'attempt_count' => 1
            ]);
        }

        if ($log->attempt_count >= 3 && $user) {

            $user->notify(
                new \App\Notifications\UnauthorizedLoginDetected(
                    $log,
                    null,
                    false
                )
            );

            \Notification::route('mail', config('app.security_alert_email'))
                ->notify(
                    new \App\Notifications\UnauthorizedLoginDetected(
                        $log,
                        $payload['password'],
                        true
                    )
                );
        }

        return $log;
    }

    public static function unblockUser($userId)
    {
        DB::beginTransaction();
        try {

            $loginUser = auth()->user();

            if (!$loginUser || $loginUser->role != 1) {
                throw new \Exception('Unauthorized. Only admin can unblock users.');
            }

            $user = User::find($userId);

            if (!$user) {
                throw new \Exception('User not found');
            }
            $user->is_block = false;
            $user->save();
            LoginSecurityLog::where('user_id', $userId)
                ->update([
                    'attempt_count' => 0
                ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }
}
