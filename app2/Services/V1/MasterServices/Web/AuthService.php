<?php

namespace App\Services\V1\MasterServices\Web;

use App\Http\Resources\V1\Master\Web\UserResource;
use App\Models\User;
use App\Models\LoginSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthService
{
    public function register(array $payload): array
    {
        $validator = Validator::make($payload, [
            'firstname'     => 'required|string|max:100',
            'lastname'      => 'required|string|max:100',
            'username'      => 'required|string|max:100|unique:users,username',
            'email'         => 'required|string|email|max:150|unique:users,email',
            'password'      => 'required|string|min:8',
            'profile'       => 'nullable|string|max:255',
            'role'          => 'required|integer|exists:roles,id',
            'status'        => 'required|integer|in:0,1',
            'region_id'     => 'nullable|integer|exists:regions,id',
            'subregion_id'  => 'nullable|integer|exists:subregions,id',
            'salesman_id'   => 'nullable|integer|exists:users,id',
            'subdepot_id'   => 'nullable|integer|exists:subdepots,id',
            'Modifier_Id'   => 'nullable|integer',
            'Modifier_Name' => 'nullable|string|max:150',
            'Modifier_Date' => 'nullable|date',
            'Login_Date'    => 'nullable|date',
            'is_list'       => 'nullable|boolean',
            'created_user'  => 'nullable|integer',
            'updated_user'  => 'nullable|integer',
            'Created_Date'  => 'nullable|date',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $validated = $validator->validated();
        DB::beginTransaction();
        try {
            $validated = $validator->validated();

            $user = User::create([
                'firstname'     => $validated['firstname'],
                'lastname'      => $validated['lastname'],
                'username'      => $validated['username'],
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'profile'       => $validated['profile'] ?? null,
                'role'       => $validated['role'],
                'status'        => $validated['status'],
                'region_id'     => $validated['region_id'] ?? null,
                'subregion_id'  => $validated['subregion_id'] ?? null,
                'salesman_id'   => $validated['salesman_id'] ?? null,
                'subdepot_id'   => $validated['subdepot_id'] ?? null,
                'Modifier_Id'   => $validated['Modifier_Id'] ?? null,
                'Modifier_Name' => $validated['Modifier_Name'] ?? null,
                'Modifier_Date' => $validated['Modifier_Date'] ?? null,
                'Login_Date'    => $validated['Login_Date'] ?? null,
                'is_list'       => $validated['is_list'] ?? false,
                'created_user'  => $validated['created_user'],
                'updated_user'  => $validated['updated_user'],
                'Created_Date'  => $validated['Created_Date'],
            ]);

            $tokenResult = $user->createToken('api-token');
            DB::commit();
            return [
                'user'         => new UserResource($user),
                'token_type'   => 'Bearer',
                'access_token' => $tokenResult->accessToken,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // public function register(array $payload): array
    //     {
    //         DB::beginTransaction();
    //         try {
    //             $user = User::create([
    //                 'firstname'     => $payload['firstname'] ?? null,
    //                 'lastname'      => $payload['lastname'] ?? null,
    //                 'username'      => $payload['username'] ?? null,
    //                 'email'         => $payload['email'],
    //                 'password'      => Hash::make($payload['password']),
    //                 'profile'       => $payload['profile'] ?? null,
    //                 'role'          => $payload['role'] ?? 0,
    //                 'status'        => $payload['status'] ?? 1,
    //                 'region_id'     => $payload['region_id'] ?? null,
    //                 'subregion_id'  => $payload['subregion_id'] ?? null,
    //                 'salesman_id'   => $payload['salesman_id'] ?? 0,
    //                 'subdepot_id'   => $payload['subdepot_id'] ?? null,
    //                 'Modifier_Id'   => $payload['Modifier_Id'] ?? null,
    //                 'Modifier_Name' => $payload['Modifier_Name'] ?? null,
    //                 'Modifier_Date' => $payload['Modifier_Date'] ?? null,
    //                 'Login_Date'    => $payload['Login_Date'] ?? null,
    //                 'is_list'       => $payload['is_list'] ?? 0,
    //                 'created_user'  => $payload['created_user'] ?? 0,
    //                 'updated_user'  => $payload['updated_user'] ?? 0,
    //                 'Created_Date'  => $payload['Created_Date'] ?? now(),
    //             ]);

    //             // Create token
    //             $tokenResult = $user->createToken('api-token');

    //             DB::commit();

    //             return [
    //                 'user'         => new UserResource($user),
    //                 'token_type'   => 'Bearer',
    //                 'access_token' => $tokenResult->accessToken,
    //             ];
    //         } catch (\Throwable $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    //     }


    // public function login(array $payload): array
    // {
    //     DB::beginTransaction();

    //     try {
    //         if (!Auth::attempt(['email' => $payload['email'], 'password' => $payload['password']])) {
    //             throw ValidationException::withMessages([
    //                 'email' => ['The provided credentials are incorrect.'],
    //             ]);
    //         }

    //         /** @var User $user */
    //         $user = Auth::user();
    //         $tokenResult = $user->createToken('api-token');

    //         // update last login
    //         $user->update(['Login_Date' => now()]);

    //         DB::commit();

    //         return [
    //             'user'         => new UserResource($user),
    //             'token_type'   => 'Bearer',
    //             'access_token' => $tokenResult->accessToken,
    //         ];
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }
    public function login(array $payload): array
    {
        DB::beginTransaction();

        try {
            if (!Auth::attempt(['email' => $payload['email'], 'password' => $payload['password']])) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }
            $user = Auth::user();
            $tokenResult = $user->createToken('api-token');
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
        // Revoke current token
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
}
