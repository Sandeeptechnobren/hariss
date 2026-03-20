<?php

namespace App\Services\V1\MasterServices\Mob;

use App\Models\Salesman;
use App\Models\VersionControll;
use Illuminate\Support\Facades\Hash;

class SalesmanMobService
{
    public function login($username, $password, $version)
    {
        $salesman = Salesman::where('osa_code', $username)->first();
        if (!$salesman) {
            return [
                'status' => false,
                'message' => 'Invalid username.',
            ];
        }
        if (!Hash::check($password, $salesman->password)) {
            return [
                'status' => false,
                'message' => 'Invalid password.',
            ];
        }
        $latestVersion = VersionControll::latest('id')->first();
        if (!$latestVersion || $latestVersion->version !== $version) {
            return [
                'status' => false,
                'message' => 'App version is outdated. Please update to continue.',
                'latest_version' => $latestVersion ? $latestVersion->version : null,
            ];
        }
        //  $tokenResult = $salesman->createToken('salesman-token');
        //  $accessToken = $tokenResult->accessToken;

        return [
            'status' => true,
            'message' => 'Login successful.',
            'data' => $salesman,
            // 'token' => $accessToken,
        ];
    }
}