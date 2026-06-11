<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    public function send(string $email, string $type): OtpCode
    {
        OtpCode::where('email', $email)
            ->where('type', $type)
            ->where('expires_at', '<=', now())
            ->delete();

        $otp = OtpCode::create([
            'email' => $email,
            'code' => (string) random_int(100000, 999999),
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        Notification::route('mail', $email)->notify(new OtpNotification($otp->code, $type));

        return $otp;
    }

    /**
     * Only the most recently issued OTP is accepted.
     */
    public function check(string $email, string $code, string $type): bool
    {
        $latest = $this->latestActive($email, $type);

        return $latest !== null && hash_equals($latest->code, $code);
    }

    /**
     * Verify against the latest OTP and, on success, delete every
     * OTP of this type for the email so none can be reused.
     */
    public function verify(string $email, string $code, string $type): bool
    {
        if (! $this->check($email, $code, $type)) {
            return false;
        }

        OtpCode::where('email', $email)->where('type', $type)->delete();

        return true;
    }

    private function latestActive(string $email, string $type): ?OtpCode
    {
        return OtpCode::where('email', $email)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }
}
