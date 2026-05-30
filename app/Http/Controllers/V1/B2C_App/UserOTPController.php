<?php

namespace App\Http\Controllers\V1\B2C_App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\V1\B2C_App\UserOTPService;

class UserOTPController extends Controller
{
    protected $otpService;

    public function __construct(UserOTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    // 🔹 Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);

        $result = $this->otpService->sendOtp($request->phone);

        if (!$result['status']) {
            return response()->json([
                'error' => $result['message']
            ], 500);
        }

        return response()->json([
            'message' => $result['message']
        ]);
    }

    // 🔹 Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'otp'   => 'required',
        ]);

        $result = $this->otpService->verifyOtpAndLogin(
            $request->phone,
            $request->otp
        );

        if (!$result['status']) {
            return response()->json([
                'error' => $result['message']
            ], 400);
        }

        return response()->json([
            'message'      => $result['message'],
            'access_token' => $result['access_token'],
            'token_type'   => $result['token_type'],
            'expires_at'   => $result['expires_at'],
            'user'         => $result['user'],
        ]);
    }
}
