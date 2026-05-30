<?php

namespace App\Services\V1\B2C_App;

use Carbon\Carbon;
use App\Models\B2CUserOtp;
use App\Services\V1\B2C_App\WhatsAppService;
use App\Models\AgentCustomer;
use Illuminate\Support\Facades\Auth;

class UserOTPService
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function generateOtp($phone)
    {
        // delete old OTP
        B2CUserOtp::where('phone', $phone)->delete();

        return rand(100000, 999999);
    }

    public function sendOtp($phone)
    {
        // 🔍 Step 1: Check customer exists
        $customer = AgentCustomer::where('whatsapp_no', $phone)->first();

        if (!$customer) {
            return [
                'status' => false,
                'message' => 'Customer not found'
            ];
        }

        // 🔢 Step 2: Generate OTP
        $otp = $this->generateOtp($phone);

        // 📲 Step 3: Send OTP via WhatsApp
        // $sent = $this->whatsappService->sendOtp($phone, $otp);

        // if (!$sent) {
        //     return [
        //         'status' => false,
        //         'message' => 'Failed to send OTP'
        //     ];
        // }

        // 💾 Step 4: Store OTP
        B2CUserOtp::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(10),
            'created_at' => now()
        ]);

        return [
            'status' => true,
            'message' => 'OTP sent successfully'
        ];
    }
    public function verifyOtpAndLogin($phone, $otp)
    {
        // Normalize phone (important)
        $phone = ltrim($phone, '+');

        // Get latest OTP only
        $record = B2CUserOtp::where('phone', $phone)
            ->latest()
            ->first();

        if (!$record) {
            return ['status' => false, 'message' => 'OTP not found'];
        }

        // Check expiry
        if (now()->gt($record->expires_at)) {
            $record->delete();
            return ['status' => false, 'message' => 'OTP expired'];
        }

        // Match OTP
        if ($record->attempts >= 5) {
            $record->delete();
            return ['status' => false, 'message' => 'Too many attempts'];
        }

        if ($record->otp != $otp) {
            $record->increment('attempts');
            return ['status' => false, 'message' => 'Invalid OTP'];
        }

        // ✅ Invalidate immediately
        $record->delete();

        // 🔍 Find customer
        $user = AgentCustomer::where('whatsapp_no', $phone)->first();

        if (!$user) {
            return ['status' => false, 'message' => 'Customer not found'];
        }

        // 🎟️ Create Passport token
        $tokenResult = $user->createToken('B2C App Token');

        return [
            'status' => true,
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
        ];
    }
}
