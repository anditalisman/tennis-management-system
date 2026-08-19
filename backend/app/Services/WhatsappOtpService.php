<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class WhatsappOtpService
{
    private const TTL_MINUTES = 5;

    public function generateAndSend(User $user): void
    {
        $code = $this->generate($this->registrationCacheKey($user));

        Notification::queue(
            $user,
            Notification::CHANNEL_WHATSAPP,
            'Kode Verifikasi — Zul Tennis Clinic',
            "Kode verifikasi Anda: {$code}\n\n".
            'Berlaku '.self::TTL_MINUTES." menit. Jangan bagikan kode ini kepada siapa pun. Anda tidak bisa masuk ke portal sebelum nomor WhatsApp ini diverifikasi.",
        );
    }

    public function verify(User $user, string $code): bool
    {
        if (! $this->check($this->registrationCacheKey($user), $code)) {
            return false;
        }

        $user->markWhatsappAsVerified();

        return true;
    }

    public function generateAndSendPasswordReset(User $user): void
    {
        $code = $this->generate($this->passwordResetCacheKey($user));

        Notification::queue(
            $user,
            Notification::CHANNEL_WHATSAPP,
            'Kode Reset Kata Sandi — Zul Tennis Clinic',
            "Kode reset kata sandi Anda: {$code}\n\n".
            'Berlaku '.self::TTL_MINUTES.' menit. Jika Anda tidak meminta ini, abaikan pesan ini — kata sandi Anda tetap aman.',
        );
    }

    public function verifyPasswordResetCode(User $user, string $code): bool
    {
        return $this->check($this->passwordResetCacheKey($user), $code);
    }

    private function generate(string $key): string
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($key, $code, now()->addMinutes(self::TTL_MINUTES));

        return $code;
    }

    private function check(string $key, string $code): bool
    {
        $expected = Cache::get($key);

        if (! $expected || ! hash_equals($expected, $code)) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function registrationCacheKey(User $user): string
    {
        return "whatsapp_otp:{$user->id}";
    }

    private function passwordResetCacheKey(User $user): string
    {
        return "password_reset_otp:{$user->id}";
    }
}
