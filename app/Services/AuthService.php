<?php

namespace App\Services\MasterServices;

use App\Http\MasterResources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $payload): array
    {
        $user = User::create([
            'name'     => $payload['name'] ?? null,
            'email'      => $payload['email'] ?? null,
            'username'      => $payload['username'] ?? null,
            'contact_number'         => $payload['contact_number'],
            'password'      => Hash::make($payload['password']),
            'profile_picture'       => $payload['profile_picture'] ?? null,
            'role'          => $payload['role'] ?? 0,   // default user
            'status'        => $payload['status'] ?? 1, // default active 
            // 'region_id'     => $payload['region_id'] ?? null,
            // 'subregion_id'  => $payload['subregion_id'] ?? null,
            // 'salesman_id'   => $payload['salesman_id'] ?? 0,
            // 'subdepot_id'   => $payload['subdepot_id'] ?? null,
            // 'Modifier_Id'   => $payload['Modifier_Id'] ?? null,
            // 'Modifier_Name' => $payload['Modifier_Name'] ?? null,
            // 'Modifier_Date' => $payload['Modifier_Date'] ?? null, // keep null unless passed
            // 'Login_Date'    => $payload['Login_Date'] ?? null,
            // 'is_list'       => $payload['is_list'] ?? 0,
            'created_user'  => $payload['created_user'] ?? 0,
            'updated_user'  => $payload['updated_user'] ?? 0,
            'Created_Date'  => $payload['Created_Date'] ?? now(),
        ]);
        $tokenResult = $user->createToken('api-token');

        return [
            'user'        => new UserResource($user),
            'token_type'  => 'Bearer',
            'access_token' => $tokenResult->accessToken,
        ];
    }


    public function login(array $payload): array
    {
        if (!Auth::attempt(['email' => $payload['email'], 'password' => $payload['password']])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $user = Auth::user();
        $tokenResult = $user->createToken('api-token');
        $user->update(['Login_Date' => now()]);
        return [
            'user' => new UserResource($user),
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->accessToken,
        ];
    }

    public function me(User $id): UserResource
    {
        return new UserResource($id);
    }
}