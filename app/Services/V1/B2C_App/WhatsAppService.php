<?php

namespace App\Services\V1\B2C_App;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around the WhApi HTTP gateway for sending WhatsApp
 * text messages. Centralised here so:
 *
 *   - Phone normalisation (strip non-digits, prepend India country
 *     code if it looks like a 10-digit local number) lives in one
 *     place rather than copy-pasted into every caller.
 *   - WhApi config (`token`, `url`, `app_name`) is read once via
 *     `config('services.whapi.*')` — callers never touch env / config.
 *   - Failure semantics are uniform: methods return `bool` and log
 *     to Laravel's default channel on failure. Callers don't need
 *     a try/catch and a transient WhApi outage cannot bubble a 500
 *     up to the HTTP layer.
 *
 * Add new templated message types as their own public method (e.g.
 * `sendApprovalReminder`, `sendMatchAlert`) so the call sites stay
 * readable. Generic ad-hoc messages can use `sendMessage()`.
 */
class WhatsAppService
{
    protected ?string $token;
    protected ?string $url;
    protected string $appName;

    public function __construct()
    {
        $this->token   = config('services.whapi.token');
        $this->url     = config('services.whapi.url');
        $this->appName = (string) config('services.whapi.app_name', 'B2C User');
    }

    /**
     * Send an arbitrary text message to a WhatsApp number.
     *
     * Returns true when WhApi accepted the message, false otherwise
     * (with the failure logged). Empty / unconfigured / invalid-phone
     * cases short-circuit to false without throwing so callers can
     * stay one-liner clean.
     */
    public function sendMessage(?string $phone, string $body): bool
    {
        if (empty($phone)) {
            return false;
        }
        // dd($this->token);
        if (empty($this->token) || empty($this->url)) {
            Log::warning('WhatsApp send skipped — WhApi not configured', [
                'phone' => $phone,
            ]);
            return false;
        }

        $normalised = $this->normalisePhone($phone);
        if ($normalised === null) {
            Log::warning('WhatsApp send skipped — phone could not be normalised', [
                'phone' => $phone,
            ]);
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->post(rtrim($this->url, '/') . '/messages/text', [
                    'to'   => $normalised,
                    'body' => $body,
                ]);

            if ($response->failed()) {
                Log::error('WhatsApp message failed', [
                    'phone'  => $normalised,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::error('WhatsApp message threw', [
                'phone' => $normalised,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send the standard 10-minute-validity OTP message.
     *
     * Templated body: "Your OTP for {APP_NAME} is: {OTP}. It is
     * valid for 10 minutes." — kept verbatim from the previous
     * inline implementation so existing users don't see a copy
     * change.
     */
    public function sendOtp(?string $phone, int|string $otp): bool
    {
        $body = sprintf(
            'Your OTP for %s is: %s. It is valid for 10 minutes.',
            $this->appName,
            $otp
        );
        // dd($body);
        return $this->sendMessage($phone, $body);
    }

    /**
     * Coach application APPROVED notification. Mirrors the
     * ApplicationApprovedMail email — same tone, just trimmed for
     * the WhatsApp character budget (no HTML, no SCM brand chrome).
     *
     * The user_code is included so the coach has their public-facing
     * code at hand for any onboarding follow-ups.
     */
    public function sendApplicationApproved(?string $phone, ?string $name = null, ?string $userCode = null): bool
    {
        $greeting = $name && trim($name) !== '' ? "Hi {$name}," : 'Hi,';
        $codeLine = $userCode ? " Your coach code is *{$userCode}*." : '';

        $body = sprintf(
            "%s your %s coach application has been approved! 🎉%s\n\nYou can now log in and start adding players.",
            $greeting,
            $this->appName,
            $codeLine
        );

        return $this->sendMessage($phone, $body);
    }

    /**
     * Coach application REJECTED notification. The optional reason is
     * surfaced verbatim — same as the email body's @if($reason) block.
     * If no reason was provided, falls back to a generic "please
     * contact support" message so the coach has a path forward.
     */
    public function sendApplicationRejected(?string $phone, ?string $name = null, ?string $reason = null): bool
    {
        $greeting = $name && trim($name) !== '' ? "Hi {$name}," : 'Hi,';
        $reasonClean = $reason !== null ? trim($reason) : '';

        if ($reasonClean !== '') {
            $body = sprintf(
                "%s your %s coach application has not been approved at this time.\n\nReason: %s\n\nPlease contact support if you'd like to discuss next steps.",
                $greeting,
                $this->appName,
                $reasonClean
            );
        } else {
            $body = sprintf(
                "%s your %s coach application has not been approved at this time.\n\nPlease contact support if you'd like to discuss next steps.",
                $greeting,
                $this->appName
            );
        }

        return $this->sendMessage($phone, $body);
    }

    /**
     * Strip non-digits from a phone number and prepend India's country
     * code when the result is 10 digits long. Numbers already 11+
     * digits are assumed to include a country code and are left alone.
     *
     * Returns null when the input has no digits at all.
     */
    private function normalisePhone(string $raw): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            $digits = '91' . $digits;
        }

        return $digits;
    }
}
