<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    public function send(string $email, string $type): OtpCode
    {
        OtpCode::where('email', $email)->where('type', $type)->whereNull('used_at')->delete();

        $otp = OtpCode::create([
            'email' => $email,
            'code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        Notification::route('mail', $email)->notify(new OtpNotification($otp->code, $type));

        return $otp;
    }

    public function check(string $email, string $code, string $type): bool
    {
        return OtpCode::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function verify(string $email, string $code, string $type): bool
    {
        $otp = OtpCode::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}
